<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProducedItemModel;
use App\Http\Controllers\v1\History\PrintHistoryController;
use App\Models\Productions\ProductionOTAModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use App\Models\Settings\Items\ItemMasterdataModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
use DB;
use App\Traits\CrudOperationsTrait;

class ProductionOTAController extends Controller
{
    use CrudOperationsTrait;
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
        return $this->readCurrentRecord(ProductionOTAModel::class, $id, $whereFields, null, null, 'Production OTA');
    }
    public function onGetEndorsedByQa($id = null)
    {

        try {
            $itemDisposition = ItemDispositionModel::with('productionBatch')
                ->where('production_type', 1)
                ->where('production_status', 1)
                ->whereNotNull('action');

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
                $itemDisposition->fulfilled_by_id = $fields['created_by_id'];
                $itemDisposition->fulfilled_at = now();
                $itemDisposition->production_status = 0;
                $itemDisposition->save();
                $this->createProductionLog(ItemDispositionModel::class, $itemDisposition->id, $itemDisposition->getAttributes(), $fields['created_by_id'], 1, $itemDisposition->item_key);
                $producedItemModel = ProducedItemModel::where('production_batch_id', $itemDisposition->production_batch_id)->first();
                $producedItems = json_decode($producedItemModel->produced_items, true);

                $itemStatus = $itemStatusArr[$producedItems[$itemDisposition->item_key]['status']];
                $statusFlag = $producedItems[$itemDisposition->item_key]['status'];
                $producedItems[$itemDisposition->item_key]['sticker_status'] = 0;
                $producedItems[$itemDisposition->item_key]['status'] = $itemStatus;
                if (isset($fields['chilled_exp_date'])) {
                    $producedItems[$itemDisposition->item_key]['new_chilled_exp_date'] = $fields['chilled_exp_date'];
                }
                if (isset($fields['frozen_exp_date'])) {
                    $producedItems[$itemDisposition->item_key]['new_frozen_exp_date'] = $fields['frozen_exp_date'];
                }

                $producedItemModel->produced_items = json_encode($producedItems);
                $producedItemModel->save();
                $this->createProductionLog(ProducedItemModel::class, $producedItemModel->id, $producedItems[$itemDisposition->item_key], $fields['created_by_id'], 1, $itemDisposition->item_key);

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

                    $data = [
                        'produced_items' => json_encode([$itemDisposition->item_key => $producedItems[$itemDisposition->item_key]]),
                        'production_batch_id' => $itemDisposition->production_batch_id
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
                        'production_batch_id' => json_decode($productionItem->content(), true)['success']['data']['mos_production_batches']['id']
                    ];
                }

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
            $productionOta = ProductionOTAModel::where('production_order_id', $productionOrder->id)->get();
            $productionOtaId = null;
            $isExist = false;
            foreach ($productionOta as $value) {
                if ($value->item_code == $itemCode) {
                    $value->actual_quantity += $quantity;
                    $productionOtaId = $value->id;
                    $value->save();
                    $isExist = true;
                    $this->createProductionLog(ProductionOTAModel::class, $value->id, $value, $fields['created_by_id'], 1);
                    break;
                }
            }

            if (!$isExist) {
                $otaRequest = new Request([
                    'created_by_id' => $fields['created_by_id'],
                    'production_order_id' => $productionOrder->id,
                    'buffer_level' => 0,
                    'item_code' => $itemCode,
                    'requested_quantity' => 0,
                    'plotted_quantity' => 0,
                    'actual_quantity' => $quantity,
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
