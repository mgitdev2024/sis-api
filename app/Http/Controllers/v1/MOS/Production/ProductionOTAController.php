<?php

namespace App\Http\Controllers\v1\MOS\Production;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Http\Controllers\v1\History\PrintHistoryController;
use App\Models\MOS\Production\ProductionOTAModel;
use App\Models\MOS\Production\ProductionOTBModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
use DB;
use App\Traits\MOS\MosCrudOperationsTrait;

class ProductionOTAController extends Controller
{
    use MosCrudOperationsTrait;
    use ResponseTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required',
            'production_order_id' => 'required|exists:mos_production_orders,id',
            'buffer_level' => 'required|integer',
            'item_code' => 'required',
            'requested_quantity' => 'required',
            'plotted_quantity' => 'required|integer',
            'actual_quantity' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ProductionOTAModel::class, $request, $this->getRules(), 'Production OTA');
    }
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'updated_by_id' => 'required',
            'plotted_quantity' => 'required|integer',
            'actual_quantity' => 'nullable|integer',
        ];
        return $this->updateRecordById(ProductionOTAModel::class, $request, $rules, 'Production OTA', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOTAModel::class, $request, $searchableFields, 'Production OTA');
    }
    public function onGetall()
    {
        return $this->readRecord(ProductionOTAModel::class, 'Production OTA');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOTAModel::class, $id, 'Production OTA');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ProductionOTAModel::class, $id, 'Production OTA');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ProductionOTAModel::class, $id, 'Production OTA', $request);
    }
    public function onGetCurrent($id = null)
    {
        try {
            $productionOrderId = null;
            if ($id != null) {
                $productionOrderId = $id;
            } else {
                $productionOrder = new ProductionOrderController();
                $currentProductionOrder = $productionOrder->onGetCurrent();
                if (isset($currentProductionOrder->getOriginalContent()['error'])) {
                    return $currentProductionOrder->getOriginalContent();
                }
                $productionOrderId = $currentProductionOrder->getOriginalContent()['success']['data'][0]['id'];
            }
            $productionOta = [];
            $excludedItemCode = ItemMasterdataModel::getViewableOtb(true);
            $productionOtas = ProductionOtaModel::with('itemMasterdata')
                ->where('production_order_id', $productionOrderId)
                ->whereNotIn('item_code', $excludedItemCode)
                ->get();

            foreach ($productionOtas as $value) {
                $productionOta[] = $value;
            }
            if (count($productionOta) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $productionOta);
            }
            return $this->dataResponse('success', 200, __('msg.record_not_found'), $productionOta);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetCurrentForOtb($id = null)
    {
        try {
            $productionOrderId = null;
            if ($id != null) {
                $productionOrderId = $id;
            } else {
                $productionOrder = new ProductionOrderController();
                $currentProductionOrder = $productionOrder->onGetCurrent();
                $productionOrderId = $currentProductionOrder->getOriginalContent()['success']['data'][0]['id'];
            }

            $productionOtaForOtb = [];
            $includedItemCode = ItemMasterdataModel::getViewableOtb(true);
            $productionOtas = ProductionOtaModel::with('itemMasterdata')
                ->where('production_order_id', $productionOrderId)
                ->whereIn('item_code', $includedItemCode)
                ->get();

            foreach ($productionOtas as $productionOta) {
                $productionOtaForOtb[] = $productionOta;
            }
            if (count($productionOtaForOtb) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $productionOtaForOtb);
            }
            return $this->dataResponse('success', 200, __('msg.record_not_found'), $productionOtaForOtb);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetEndorsedByQa($id = null)
    {

        try {
            $excludedItemCode = ItemMasterdataModel::getViewableOtb(true);
            $itemDisposition = ItemDispositionModel::with('productionBatch')
                ->where(function ($query) use ($excludedItemCode) {
                    $query->whereNotIn('item_code', $excludedItemCode)
                        ->where(function ($query) {
                            $query->where('production_type', 1)
                                // ->where('production_status', 1)
                                ->whereNotNull('action')
                                ->where('is_printed', 0);
                        });
                })
                // ->where('production_status', 1)
                ->whereNotNull('action')
                ->where('action', '!=', 10)
                ->where('is_printed', 0);
            if ($id != null) {
                $itemDisposition->where('id', $id);
            }
            $result = $itemDisposition->get();
            return $this->dataResponse('success', 200, __('msg.record_found'), $result);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onFulfillEndorsement(Request $request, $id)
    {

        $fields = $request->validate([
            'created_by_id' => 'required',
            'chilled_exp_date' => 'nullable|date',
            'frozen_exp_date' => 'nullable|date',
        ]);
        try {
            $itemStatusArr = [
                '6' => 11, // retouch
                '7' => 12, // slice
                '8' => 9, // sticker updated
            ];
            DB::beginTransaction();
            $itemDisposition = ItemDispositionModel::where('id', $id)
                ->where('production_type', 1)
                ->where('production_status', 1)
                ->first();

            if ($itemDisposition) {
                $productionBatchModel = ProductionBatchModel::find($itemDisposition->production_batch_id);
                $producedItemModel = $productionBatchModel->productionItems;
                $producedItems = json_decode($producedItemModel->produced_items, true);

                $itemStatus = $itemStatusArr[$producedItems[$itemDisposition->item_key]['status']];
                $statusFlag = $producedItems[$itemDisposition->item_key]['status'];
                $productionToBakeAssemble = $productionBatchModel->productionOtb ?? $productionBatchModel->productionOta;
                $modelClass = $productionBatchModel->productionOtb
                    ? ProductionOTBModel::class
                    : ProductionOTAModel::class;

                if ($itemStatus != 9) {
                    $producedItems[$itemDisposition->item_key]['endorsed_by_qa'] = 1;
                    $producedItems[$itemDisposition->item_key]['sticker_status'] = 0;
                    if ($itemStatus != 12) {
                        $productionToBakeAssemble->in_qa_count -= 1;
                        $productionToBakeAssemble->save();
                        $this->createProductionLog($modelClass, $productionToBakeAssemble->id, $productionToBakeAssemble->getAttributes(), $fields['created_by_id'], 1);
                    }
                }

                $producedItems[$itemDisposition->item_key]['status'] = $itemStatus;
                if (isset($fields['chilled_exp_date'])) {
                    $producedItems[$itemDisposition->item_key]['new_chilled_exp_date'] = $fields['chilled_exp_date'];
                }
                if (isset($fields['frozen_exp_date'])) {
                    $producedItems[$itemDisposition->item_key]['new_frozen_exp_date'] = $fields['frozen_exp_date'];
                }

                $producedItemModel->produced_items = json_encode($producedItems);
                $producedItemModel->save();
                $this->createProductionLog(ProductionItemModel::class, $producedItemModel->id, $producedItems[$itemDisposition->item_key], $fields['created_by_id'], 1, $itemDisposition->item_key);

                $itemDisposition->fulfilled_by_id = $fields['created_by_id'];
                $itemDisposition->fulfilled_at = now();
                $itemDisposition->production_status = 0;
                $itemDisposition->action = $itemStatus;
                $data = null;
                if ($itemStatus == 9) {
                    $produceItem = $producedItems[$itemDisposition->item_key];
                    $printHistory = new PrintHistoryController();
                    $printHistoryRequest = new Request([
                        'production_batch_id' => $itemDisposition->production_batch_id,
                        'produced_items' => json_encode($produceItem),
                        'is_reprint' => 0,
                        'created_by_id' => $fields['created_by_id'],
                        'item_disposition_id' => $id ?? null
                    ]);
                    $printHistory->onCreate($printHistoryRequest);
                    $itemDisposition->fulfilled_batch_id = $itemDisposition->production_batch_id;


                    $productionToBakeAssemble->produced_items_count += 1;
                    $productionToBakeAssemble->in_qa_count -= 1;
                    $productionToBakeAssemble->save();
                    $this->createProductionLog($modelClass, $productionToBakeAssemble->id, $productionToBakeAssemble->getAttributes(), $fields['created_by_id'], 1);
                    $data = [
                        'produced_items' => json_encode([$itemDisposition->item_key => $producedItems[$itemDisposition->item_key]]),
                        'production_batch_id' => $itemDisposition->production_batch_id,
                        'production_batch' => $itemDisposition->productionBatch
                    ];
                } else {
                    $productionItem = $this->onSetProductionOrderBatch(
                        $itemDisposition,
                        $itemDisposition->quantity_update,
                        $fields,
                        $statusFlag
                    );

                    $data = [
                        'produced_items' => json_decode($productionItem->content(), true)['success']['data']['production_item'],
                        'production_batch' => json_decode($productionItem->content(), true)['success']['data']['production_batch'],
                        'batch_origin' => $itemDisposition->production_batch_id,
                    ];
                    $itemDisposition->fulfilled_batch_id = json_decode($productionItem->content(), true)['success']['data']['production_batch']['id'];
                }
                $itemDisposition->save();
                $this->createProductionLog(ItemDispositionModel::class, $itemDisposition->id, $itemDisposition->getAttributes(), $fields['created_by_id'], 1, $itemDisposition->item_key);
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $data);
            }
            return $this->dataResponse('success', 200, __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onSetProductionOrderBatch($itemDisposition, $quantity, $fields, $itemStatus)
    {
        try {
            $itemCode = $itemDisposition->productionBatch->productionOta->item_code;

            if ($itemStatus == 7) {
                $itemMasterdata = ItemMasterdataModel::where('item_code', $itemCode)->first();
                $itemVariant = ItemMasterdataModel::where('parent_item_id', $itemMasterdata->id)->where('item_variant_type_id', 3)->first();
                $itemCode = $itemVariant->item_code;
            }

            $productionOrder = $itemDisposition->productionBatch->productionOrder;

            $isRetouch = 0;
            if ($quantity == null || $quantity <= 0) {
                $quantity = 1;
                $isRetouch = 1;
            }
            $productionOta = ProductionOTAModel::where([
                'production_order_id' => $productionOrder->id,
                'item_code' => $itemCode,
            ])->first();
            $productionOtaId = $productionOta->id;

            if (!$productionOta) {
                $otaRequest = new Request([
                    'created_by_id' => $fields['created_by_id'],
                    'production_order_id' => $productionOrder->id,
                    'buffer_level' => 0,
                    'item_code' => $itemCode,
                    'requested_quantity' => 0,
                    'plotted_quantity' => 0,
                    // 'actual_quantity' => $quantity,
                ]);
                $productionOtaId = $this->onCreate($otaRequest)->getOriginalContent()['success']['data']['id'];
            }
            $itemConversion = ItemMasterdataModel::where('item_code', $itemCode)->first();
            $conversionUnit = [
                $itemConversion->uom->long_name => $quantity
            ];

            $productionBatch = new ProductionBatchController();
            $productionBatchRequest = new Request([
                'production_ota_id' => $productionOtaId,
                'batch_type' => $isRetouch,
                'endorsed_by_qa' => 1,
                'item_disposition_id' => $itemDisposition->id,
                'quantity' => json_encode($conversionUnit),
                'chilled_exp_date' => $fields['chilled_exp_date'] ?? null,
                'frozen_exp_date' => $fields['frozen_exp_date'] ?? null,
                'created_by_id' => $fields['created_by_id'],
            ]);

            return $productionBatch->onCreate($productionBatchRequest);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
