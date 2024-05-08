<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\History\PrintHistoryController;
use App\Traits\ProductionHistoricalLogTrait;
use App\Models\Productions\ProducedItemModel;
use App\Models\Productions\ProductionBatchModel;
use App\Models\Productions\ProductionOTBModel;
use App\Models\Productions\ProductionOTAModel;
use App\Models\Settings\Items\ItemMasterdataModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
use DB;
use App\Traits\CrudOperationsTrait;

class ProductionBatchController extends Controller
{
    use CrudOperationsTrait, ProductionHistoricalLogTrait;
    use ResponseTrait;
    public static function onGetRules()
    {
        return [
            'production_batch_id' => 'nullable|integer|exists:production_batch,id',
            'production_ota_id' => 'nullable|exists:production_ota,id',
            'production_otb_id' => 'nullable|exists:production_otb,id',
            'batch_type' => 'required|integer|in:0,1',
            'endorsed_by_qa' => 'required|integer|in:0,1',
            'item_disposition_id' => 'required_if:endorsed_by_qa,1|integer',
            'quantity' => 'required',
            'chilled_exp_date' => 'nullable|date',
            'frozen_exp_date' => 'nullable|date',
            'created_by_id' => 'required',
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->onGetRules());

        // dd($fields);
        try {
            $batch = null;
            DB::beginTransaction();
            if (isset($fields['production_batch_id'])) {
                $batch = $this->onAddToExistingBatch($fields);
            } else {
                $batch = $this->onInitialBatch($fields);
            }

            DB::commit();
            return $this->dataResponse('success', 201, 'Production Batch ' . __('msg.create_success'), $batch);
        } catch (Exception $exception) {
            DB::rollBack();
            dd($exception);
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }

    public function onAddToExistingBatch($fields)
    {
        try {
            $productionBatch = ProductionBatchModel::find($fields['production_batch_id']);
            $productionToBakeAssemble = isset($fields['production_otb_id'])
                ? ProductionOTBModel::find($fields['production_otb_id'])
                : ProductionOTAModel::find($fields['production_ota_id']);
            $productionType = $productionBatch->production_otb_id ? 0 : 1;
            $itemMasterdata = ItemMasterdataModel::where('item_code', $productionToBakeAssemble->item_code)->first();
            $primaryPackingSize = intval($itemMasterdata->primary_item_packing_size);
            $producedItems = ProducedItemModel::where('production_batch_id', $productionBatch->id)->first();

            $endorsedQA = $fields['endorsed_by_qa'];
            $quantity = json_decode($fields['quantity'], true);
            $data = $quantity;
            $keys = array_keys($data);
            $primaryValue = 0;
            $secondaryValue = 0;

            if (isset($keys[0])) {
                $primaryValue = intval($quantity[$keys[0]]) ?? 0;
            }

            if (isset($keys[1])) {
                $secondaryValue = intval($quantity[$keys[1]]) ?? 0;
            } else {
                $secondaryValue = $primaryValue;
            }

            $stickerMultiplier = $productionBatch->productionOtb ?
                $productionBatch->productionOtb->itemMasterData->itemVariantType->sticker_multiplier :
                ($productionBatch->productionOta ?
                    $productionBatch->productionOta->itemMasterData->itemVariantType->sticker_multiplier :
                    1);

            $producedItemsArray = json_decode($producedItems->produced_items, true);
            $producedItemCount = count($producedItemsArray) + 1;
            $addedItemCount = $producedItemCount + $primaryValue;

            $addedProducedItem = [];
            for ($producedItemCount; $producedItemCount < $addedItemCount; $producedItemCount++) {
                $batchCode = $productionBatch->batch_code . '-' . str_pad($producedItemCount, 3, '0', STR_PAD_LEFT);
                if ($fields['batch_type'] == 1) {
                    $batchCode .= '-R';
                }
                $itemQuantity = $secondaryValue <= $primaryPackingSize ? $secondaryValue : $primaryPackingSize;
                $itemArray = [
                    'bid' => $productionBatch->id,
                    'q' => $itemQuantity,
                    'sticker_status' => 1,
                    'status' => 0,
                    'quality' => ProductionBatchModel::setBatchTypeLabel($fields['batch_type']),
                    'parent_batch_code' => $productionBatch->batch_code,
                    'sticker_multiplier' => $stickerMultiplier,
                    'batch_code' => $batchCode,
                    'endorsed_by_qa' => $endorsedQA
                ];
                $secondaryValue -= $primaryPackingSize;
                $addedProducedItem[$producedItemCount] = $itemArray;
                $producedItemsArray[$producedItemCount] = $itemArray;
            }
            $producedItems->production_type = $productionType;
            $producedItems->produced_items = json_encode($producedItemsArray);
            $producedItems->save();
            $this->createProductionHistoricalLog(ProducedItemModel::class, $producedItems->id, $producedItems, $fields['created_by_id'], 1);
            $this->onPrintHistory(
                $productionBatch->id,
                $addedProducedItem,
                $fields,
            );
            $productionBatchCurrent = json_decode($productionBatch->quantity, true);
            $toBeAddedQuantity = json_decode($fields['quantity'], true);

            foreach ($toBeAddedQuantity as $key => $value) {
                $productionBatchCurrent[$key] = $productionBatchCurrent[$key] + $value;
            }
            $productionBatch->quantity = json_encode($productionBatchCurrent);
            $productionBatch->save();
            $this->createProductionHistoricalLog(ProductionBatchModel::class, $productionBatch->id, $productionBatch, $fields['created_by_id'], 1);
            $data = [
                'item_name' => $itemMasterdata->description,
                'production_batch' => $productionBatch,
                'production_item' => $addedProducedItem,
            ];
            return $data;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onInitialBatch($fields)
    {
        try {
            $batchNumberProdName = null;
            $productionToBakeAssemble = null;
            if (isset($fields['production_otb_id'])) {
                $batchNumberProdName = 'production_otb_id';
                $productionToBakeAssemble = ProductionOTBModel::find($fields['production_otb_id']);
            } else {
                $batchNumberProdName = 'production_ota_id';
                $productionToBakeAssemble = ProductionOTAModel::find($fields['production_ota_id']);
            }
            $endorsedQA = $fields['endorsed_by_qa'];
            $fields['chilled_exp_date'] = $fields['chilled_exp_date'] ?? $productionToBakeAssemble->expected_chilled_exp_date;
            $fields['frozen_exp_date'] = $fields['frozen_exp_date'] ?? $productionToBakeAssemble->expected_frozen_exp_date;
            $itemCode = $productionToBakeAssemble->item_code;
            $deliveryType = $productionToBakeAssemble->delivery_type;
            $batchNumber = count(ProductionBatchModel::where($batchNumberProdName, $productionToBakeAssemble->id)->get()) + 1;
            $batchCode = ProductionBatchModel::generateBatchCode(
                $itemCode,
                $deliveryType,
                $batchNumber
            );
            $productionBatch = new ProductionBatchModel();
            $productionBatch->fill($fields);
            $productionBatch->batch_code = $batchCode;
            $productionBatch->batch_number = $batchNumber;
            $productionBatch->status = 0;
            $productionBatch->production_order_id = $productionToBakeAssemble->productionOrder->id;
            $productionBatch->save();

            $itemName = ItemMasterdataModel::where('item_code', $itemCode)->first();
            $this->createProductionHistoricalLog(ProductionBatchModel::class, $productionBatch->id, $productionBatch, $fields['created_by_id'], 1);
            $data = [
                'item_name' => $itemName->description,
                'production_batch' => $productionBatch,
                'production_item' => $this->onGenerateProducedItems(
                    $productionBatch,
                    $fields['batch_type'],
                    $endorsedQA,
                    $fields
                )->produced_items
            ];
            return $data;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }

    public function onGenerateProducedItems($productionBatch, $batchType, $endorsedQA, $fields)
    {
        try {
            return $this->onInitialProducedItems($productionBatch, $batchType, $endorsedQA, $fields);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }

    public function onInitialProducedItems($productionBatch, $batchType, $endorsedQA, $fields)
    {
        try {

            $quantity = json_decode($productionBatch->quantity, true);
            $data = $quantity;
            $keys = array_keys($data);
            $primaryValue = 0;
            $secondaryValue = 0;

            if (isset($keys[0])) {
                $primaryValue = intval($quantity[$keys[0]]) ?? 0;
            }

            if (isset($keys[1])) {
                $secondaryValue = intval($quantity[$keys[1]]) ?? 0;
            } else {
                $secondaryValue = $primaryValue;
            }
            $productionToBakeAssemble = $productionBatch->production_otb_id
                ? ProductionOTBModel::find($productionBatch->production_otb_id)
                : ProductionOTAModel::find($productionBatch->production_ota_id);

            $productionType = $productionBatch->production_otb_id ? 0 : 1;

            $itemMasterdata = ItemMasterdataModel::where('item_code', $productionToBakeAssemble->item_code)->first();
            $primaryPackingSize = intval($itemMasterdata->primary_item_packing_size) > 0 ? intval($itemMasterdata->primary_item_packing_size) : 1;

            $producedItems = new ProducedItemModel();
            $producedItems->production_batch_id = $productionBatch->id;
            $producedItems->created_by_id = $productionBatch->created_by_id;

            $producedItemsArray = [];
            $stickerMultiplier = $productionBatch->productionOtb ?
                $productionBatch->productionOtb->itemMasterData->itemVariantType->sticker_multiplier :
                ($productionBatch->productionOta ?
                    $productionBatch->productionOta->itemMasterData->itemVariantType->sticker_multiplier :
                    1);

            for ($i = 1; $i <= $primaryValue; $i++) {
                $batchCode = $productionBatch->batch_code . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                if ($batchType == 1) {
                    $batchCode .= '-R';
                }
                $itemQuantity = $secondaryValue <= $primaryPackingSize ? $secondaryValue : $primaryPackingSize;

                $itemArray = [
                    'bid' => $productionBatch->id,
                    'q' => $itemQuantity,
                    'sticker_status' => 1,
                    'status' => 0,
                    'quality' => ProductionBatchModel::setBatchTypeLabel($batchType),
                    'parent_batch_code' => $productionBatch->batch_code,
                    'sticker_multiplier' => $stickerMultiplier,
                    'batch_code' => $batchCode,
                    'endorsed_by_qa' => $endorsedQA
                ];
                $secondaryValue -= $primaryPackingSize;
                $producedItemsArray[$i] = $itemArray;
            }
            $producedItems->production_type = $productionType;
            $producedItems->produced_items = json_encode($producedItemsArray);
            $producedItems->save();
            $this->createProductionHistoricalLog(ProducedItemModel::class, $producedItems->id, $producedItems, $fields['created_by_id'], 1);
            $this->onPrintHistory($productionBatch->id, $producedItemsArray, $fields);
            $productionBatch->produced_item_id = $producedItems->id;
            $productionBatch->save();
            return $producedItems;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'updated_by_id' => 'required',
            'chilled_exp_date' => 'required|date',
        ];
        return $this->updateRecordById(ProductionBatchModel::class, $request, $rules, 'Production Batches', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionBatchModel::class, $request, $searchableFields, 'Production Batches');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionBatchModel::class, $id, 'Production Batches');
    }

    public function onGetCurrent($id = null, $order_type = null)
    {
        try {
            $data = ProductionBatchModel::orderBy('id', 'ASC');
            if (strcasecmp($order_type, 'otb') == 0) {
                $data->where('production_otb_id', $id);
            } else if (strcasecmp($order_type, 'ota') == 0) {
                $data->where('production_ota_id', $id);
            }

            $result = $data->get();
            foreach ($result as $value) {
                $activeStickers = 0;
                $inactiveStickers = 0;
                $producedItems = json_decode($value->producedItem->produced_items, true);
                foreach ($producedItems as $key => $items) {
                    if ($items['sticker_status'] == 1) {
                        ++$activeStickers;
                    } else {
                        ++$inactiveStickers;
                    }
                }
                $value->active_stickers = $activeStickers;
                $value->inactive_stickers = $inactiveStickers;
            }
            if (count($result) > 0) {
                return $this->dataResponse('success', 200, 'Production Batch ' . __('msg.record_found'), $result);
            }
            return $this->dataResponse('error', 200, 'Production Batch ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onPrintHistory($batchId, $producedItems, $fields)
    {

        try {
            $printHistory = new PrintHistoryController();
            $printHistoryRequest = new Request([
                'production_batch_id' => $batchId,
                'produced_items' => json_encode(array_keys($producedItems)),
                'is_reprint' => 0,
                'created_by_id' => $fields['created_by_id'],
                'item_disposition_id' => $fields['item_disposition_id'] ?? null
            ]);

            $printHistory->onCreate($printHistoryRequest);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onGetProductionBatchMetalLine($orderType)
    {
        try {
            // 0 = otb, 1 = ota
            $orderTypeString = $orderType == 0 ? 'production_otb_id' : 'production_ota_id';
            $productionBatch = ProductionBatchModel::with('productionOrder')
                ->whereNotNull($orderTypeString)
                ->whereHas('productionOrder', function ($query) {
                    $query->where('status', '=', 0);
                })
                ->get();

            if (count($productionBatch) > 0) {
                return $this->dataResponse('success', 200, 'Production Batch ' . __('msg.record_found'), $productionBatch);
            }
            return $this->dataResponse('error', 200, 'Production Batch ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onSetInitialPrint($id)
    {
        try {
            $productionBatch = ProductionBatchModel::find($id);
            $productionBatch->is_printed = 1;
            $productionBatch->save();
            return $this->dataResponse('success', 201, 'Production Batch ' . __('msg.update_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }
}

