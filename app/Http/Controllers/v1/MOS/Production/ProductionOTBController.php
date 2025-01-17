<?php

namespace App\Http\Controllers\v1\MOS\Production;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\History\PrintHistoryController;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\MOS\Production\ProductionOTAModel;
use App\Models\MOS\Production\ProductionOTBModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Http\Request;
use App\Traits\MOS\MosCrudOperationsTrait;
use DB;
use Exception;

class ProductionOTBController extends Controller
{
    use MosCrudOperationsTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'production_order_id' => 'required|exists:mos_production_orders,id',
            'item_code' => 'required|string',
            // 'production_date' => 'required|date_format:Y-m-d',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(ProductionOTBModel::class, $request, $this->getRules(), 'Production OTB');
    }
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'updated_by_id' => 'required',
            'plotted_quantity' => 'required|integer',
            'actual_quantity' => 'nullable|integer',
        ];
        return $this->updateRecordById(ProductionOTBModel::class, $request, $rules, 'Production OTB', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOTBModel::class, $request, $searchableFields, 'Production OTB');
    }
    public function onGetAll()
    {
        return $this->readRecord(ProductionOTBModel::class, 'Production OTB');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOTBModel::class, $id, 'Production OTB');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ProductionOTBModel::class, $id, 'Production OTB');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ProductionOTBModel::class, $id, 'Production OTB', $request);
    }
    public function onGetCurrent($id = null)
    {

        $whereFields = [];
        if ($id != null) {
            $whereFields = [
                'production_order_id' => $id
            ];
        } else {
            $productionOrder = new ProductionOrderController();
            $currentProductionOrder = $productionOrder->onGetCurrent();

            $whereFields = [];
            if (isset($currentProductionOrder->getOriginalContent()['success'])) {
                $whereFields = [
                    'production_order_id' => $currentProductionOrder->getOriginalContent()['success']['data'][0]['id']
                ];
            }
        }
        return $this->readCurrentRecord(ProductionOTBModel::class, $id, $whereFields, null, null, 'Production OTB');
    }
    public function onGetEndorsedByQa(Request $request, $id = null)
    {
        try {
            $includedItemCode = ItemMasterdataModel::getViewableOtb(true);
            $itemDisposition = ItemDispositionModel::with('productionBatch')
                ->where(function ($query) use ($includedItemCode) {
                    $query->whereIn('item_code', $includedItemCode)
                        ->orWhere(function ($query) {
                            $query->where('production_type', 0)
                                // ->where('production_status', 1)
                                ->whereNotNull('action')
                                ->where('is_printed', 0);
                        });
                })
                // ->where('production_status', 1)
                ->whereNotNull('action')
                ->whereNotIn('action', [10, 10.1, 10.2, 10.3, 10.4])
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
            $itemDisposition = ItemDispositionModel::where('id', $id)->where('production_status', 1)->first();
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
                    $producedItems[$itemDisposition->item_key]['sticker_status'] = 0;
                    if ($itemStatus != 12) {
                        $productionToBakeAssemble->in_qa_count -= 1;
                        $productionToBakeAssemble->save();
                        $this->createProductionLog($modelClass, $productionToBakeAssemble->id, $productionToBakeAssemble->getAttributes(), $fields['created_by_id'], 1);
                    }
                }
                $producedItems[$itemDisposition->item_key]['endorsed_by_qa'] = 1;
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
                    $productionBatch = ProductionBatchModel::find($produceItem['bid']);
                    $productionBatch->has_endorsement_from_qa = 1;
                    $productionBatch->save();
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
            return $this->dataResponse('error', 200, $exception->getMessage());
        }
    }
    public function onSetProductionOrderBatch($itemDisposition, $quantity, $fields, $itemStatus)
    {
        try {
            $itemCode = $itemDisposition->productionBatch->item_code;

            if ($itemStatus == 7) {
                $itemMasterdata = ItemMasterdataModel::where('item_code', $itemCode)->first();
                $baseCode = explode(' ', $itemCode)[0];
                $itemVariant = ItemMasterdataModel::where('item_code', 'like', $baseCode . '%')
                    ->whereNotNull('parent_item_id')
                    ->where('item_variant_type_id', 3)->first();
                $itemCode = null;
                if ($itemVariant) {
                    $parentIds = json_decode($itemVariant->parent_item_id, true);
                    if (in_array($itemMasterdata->id, $parentIds)) {
                        $itemCode = $itemVariant->item_code;
                    } else {
                        throw new Exception('Please check the parent item of this item code.');
                    }
                } else {
                    throw new Exception('Please check the parent item of this item code.');
                }
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
            $productionOtaId = $productionOta->id ?? null;

            if (!$productionOta) {
                $otaController = new ProductionOTAController();
                $otaRequest = new Request([
                    'created_by_id' => $fields['created_by_id'],
                    'production_order_id' => $productionOrder->id,
                    'buffer_level' => 0,
                    'item_code' => $itemCode,
                    'requested_quantity' => 0,
                    'plotted_quantity' => 0,
                    'buffer_quantity' => 0,
                    // 'actual_quantity' => $quantity,
                ]);
                $productionOtaId = $otaController->onCreate($otaRequest)->getOriginalContent()['success']['data']['id'];
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
