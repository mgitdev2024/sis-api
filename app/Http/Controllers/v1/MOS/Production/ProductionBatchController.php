<?php

namespace App\Http\Controllers\v1\MOS\Production;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\History\PrintHistoryController;
use App\Models\QualityAssurance\ItemDispositionModel;
use App\Traits\MOS\ProductionLogTrait;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionOTBModel;
use App\Models\MOS\Production\ProductionOTAModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
use DB;
use App\Traits\MOS\MosCrudOperationsTrait;

class ProductionBatchController extends Controller
{
    use MosCrudOperationsTrait, ProductionLogTrait;
    use ResponseTrait;
    public static function onGetRules()
    {
        return [
            'production_batch_id' => 'nullable|integer|exists:mos_production_batches,id',
            'production_ota_id' => 'nullable|exists:mos_production_otas,id',
            'production_otb_id' => 'nullable|exists:mos_production_otbs,id',
            'batch_type' => 'required|integer|in:0,1',
            'endorsed_by_qa' => 'required|integer|in:0,1',
            'item_disposition_id' => 'required_if:endorsed_by_qa,1|integer',
            'quantity' => 'required',
            'production_date' => 'nullable|date',
            'chilled_exp_date' => 'nullable|date',
            'frozen_exp_date' => 'nullable|date',
            'ambient_exp_date' => 'nullable|date',
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
            return $this->dataResponse('error', 400, $exception->getMessage());
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
            $primaryPackingSize = intval($itemMasterdata->primary_item_packing_size) > 0 ? intval($itemMasterdata->primary_item_packing_size) : 1;
            $productionItems = ProductionItemModel::where('production_batch_id', $productionBatch->id)->first();

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
                $productionBatch->productionOtb->itemMasterdata->itemVariantType->stickerMultiplier->multiplier :
                ($productionBatch->productionOta ?
                    $productionBatch->productionOta->itemMasterdata->itemVariantType->stickerMultiplier->multiplier :
                    1);

            $producedItemsArray = json_decode($productionItems->produced_items, true);
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
                    'sticker_no' => $producedItemCount,
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
                $this->createProductionLog(ProductionItemModel::class, $productionItems->id, [$producedItemCount => $itemArray], $fields['created_by_id'], 1, $producedItemCount);
            }
            $productionItems->production_type = $productionType;
            $productionItems->produced_items = json_encode($producedItemsArray);
            $productionItems->save();
            $this->createProductionLog(ProductionItemModel::class, $productionItems->id, $productionItems->getAttributes(), $fields['created_by_id'], 1);
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
            $productionBatch->has_endorsement_from_qa = $endorsedQA;
            $productionBatch->quantity = json_encode($productionBatchCurrent);
            $productionBatch->save();
            $this->createProductionLog(ProductionBatchModel::class, $productionBatch->id, $productionBatch->getAttributes(), $fields['created_by_id'], 1);
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

    public function onGetExpirationDate($productionDate, $productionToBakeAssemble)
    {
        try {
            $chilledExpDate = $productionToBakeAssemble->expected_chilled_exp_date;
            $frozenExpDate = $productionToBakeAssemble->expected_frozen_exp_date;
            $ambientExpDate = $productionToBakeAssemble->expected_ambient_exp_date;
            $itemData = $productionToBakeAssemble->itemMasterdata;
            if ($productionDate != null) {
                $chilledExpDate = $itemData->chilled_shelf_life != null ? date('Y-m-d', strtotime('+' . $itemData->chilled_shelf_life . ' days', strtotime($productionDate))) : null;
                $frozenExpDate = $itemData->frozen_shelf_life != null ? date('Y-m-d', strtotime('+' . $itemData->frozen_shelf_life . ' days', strtotime($productionDate))) : null;
                $ambientExpDate = $itemData->ambient_shelf_life != null ? date('Y-m-d', strtotime('+' . $itemData->ambient_shelf_life . ' days', strtotime($productionDate))) : null;
            }
            $data = [
                'chilled_exp' => $chilledExpDate,
                'frozen_exp' => $frozenExpDate,
                'ambient_exp' => $ambientExpDate
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
            $productionDate = $fields['production_date'] ?? $productionToBakeAssemble->productionOrder->production_date;
            $expirationDate = $this->onGetExpirationDate($productionDate, $productionToBakeAssemble);
            $fields['chilled_exp_date'] = $fields['chilled_exp_date'] ?? $expirationDate['chilled_exp'];
            $fields['frozen_exp_date'] = $fields['frozen_exp_date'] ?? $expirationDate['frozen_exp'];
            $fields['ambient_exp_date'] = $fields['ambient_exp_date'] ?? $expirationDate['ambient_exp'];
            $itemCode = $productionToBakeAssemble->item_code;
            $deliveryType = $productionToBakeAssemble->delivery_type;
            $batchNumber = $this->onGetNextBatchNumber($batchNumberProdName, $productionToBakeAssemble->id);
            $batchCode = ProductionBatchModel::generateBatchCode(
                $itemCode,
                $deliveryType,
                $batchNumber,
                $productionDate
            );
            $productionBatch = new ProductionBatchModel();
            $productionBatch->fill($fields);
            $productionBatch->batch_code = $batchCode;
            $productionBatch->batch_number = $batchNumber;
            $productionBatch->has_endorsement_from_qa = $endorsedQA;
            $productionBatch->status = 0;
            $productionBatch->production_order_id = $productionToBakeAssemble->productionOrder->id;
            $productionBatch->save();

            $itemName = ItemMasterdataModel::where('item_code', $itemCode)->first();
            $this->createProductionLog(ProductionBatchModel::class, $productionBatch->id, $productionBatch->getAttributes(), $fields['created_by_id'], 0);
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
            $productionItems = new ProductionItemModel();
            $productionItems->production_batch_id = $productionBatch->id;
            $productionItems->created_by_id = $productionBatch->created_by_id;

            $producedItemsArray = [];
            $stickerMultiplier = $productionBatch->productionOtb ?
                $productionBatch->productionOtb->itemMasterdata->itemVariantType->stickerMultiplier->multiplier :
                ($productionBatch->productionOta ?
                    $productionBatch->productionOta->itemMasterdata->itemVariantType->stickerMultiplier->multiplier :
                    1);

            for ($i = 1; $i <= $primaryValue; $i++) {
                $batchCode = $productionBatch->batch_code . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                if ($batchType == 1) {
                    $batchCode .= '-R';
                }
                $itemQuantity = $secondaryValue <= $primaryPackingSize ? $secondaryValue : $primaryPackingSize;

                if ($itemQuantity <= 0) {
                    throw new Exception("Quantity discrepancy detected. Please check Item Masterdata for this item");
                }
                $itemArray = [
                    'bid' => $productionBatch->id,
                    'q' => $itemQuantity,
                    'sticker_status' => 1,
                    'sticker_no' => $i,
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
            $productionItems->production_type = $productionType;
            $productionItems->produced_items = json_encode($producedItemsArray);
            $productionItems->save();

            foreach ($producedItemsArray as $key => $value) {
                $this->createProductionLog(ProductionItemModel::class, $productionItems->id, [$key => $value], $fields['created_by_id'], 0, $key);
            }
            $this->createProductionLog(ProductionItemModel::class, $productionItems->id, $productionItems->getAttributes(), $fields['created_by_id'], 0);
            $this->onPrintHistory($productionBatch->id, $producedItemsArray, $fields);
            $productionBatch->production_item_id = $productionItems->id;
            $productionBatch->save();
            return $productionItems;
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
            $data = ProductionBatchModel::query();
            if (strcasecmp($order_type, 'otb') == 0) {
                $data->where('production_otb_id', $id);
            } else if (strcasecmp($order_type, 'ota') == 0) {
                $data->where('production_ota_id', $id);
            }
            $data->orderBy('batch_number', 'ASC');
            $result = $data->get();
            foreach ($result as $value) {
                $activeStickers = 0;
                $inactiveStickers = 0;
                $productionItems = json_decode($value->productionItems->produced_items, true);
                foreach ($productionItems as $key => $items) {
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

    public function onPrintHistory($batchId, $productionItems, $fields)
    {

        try {
            $printHistory = new PrintHistoryController();
            $printHistoryRequest = new Request([
                'production_batch_id' => $batchId,
                'produced_items' => json_encode(array_keys($productionItems)),
                'is_reprint' => 0,
                'created_by_id' => $fields['created_by_id'],
                'item_disposition_id' => $fields['item_disposition_id'] ?? null
            ]);

            $printHistory->onCreate($printHistoryRequest);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
    public function onGetProductionBatchMetalLine($orderType, $id = null)
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

            // 0 = otb, 1 = ota
            $inclusionExclusionItemCode = ItemMasterdataModel::getViewableOtb(true);

            $productionBatchAdd = ProductionBatchModel::with(['productionOtb', 'productionOta']);

            if ($orderType == 0) {
                $productionBatchAdd->where(function ($query) use ($inclusionExclusionItemCode, $productionOrderId) {
                    $query->whereHas('productionOta', function ($query) use ($inclusionExclusionItemCode) {
                        $query->whereIn('item_code', $inclusionExclusionItemCode);
                    })
                        ->orWhereNotNull('production_otb_id')
                        ->where('production_order_id', $productionOrderId);
                });
            } else {
                $productionBatchAdd->where(function ($query) use ($inclusionExclusionItemCode, $productionOrderId) {
                    $query->whereHas('productionOta', function ($query) use ($inclusionExclusionItemCode) {
                        $query->whereNotIn('item_code', $inclusionExclusionItemCode);
                    })
                        ->whereNotNull('production_ota_id')
                        ->where('production_order_id', $productionOrderId);
                });
            }

            $productionBatch = $productionBatchAdd->get();

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

            $itemDisposition = ItemDispositionModel::where([
                'production_batch_id' => $id,
                'is_printed' => 0
            ])->first();
            if ($itemDisposition) {
                $itemDisposition->is_printed = 1;
                $itemDisposition->save();
            }
            return $this->dataResponse('success', 201, 'Production Batch ' . __('msg.update_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }
    public function onGetNextBatchNumber($batchNumberProdName, $productionToBakeAssembleId)
    {
        $existingBatches = ProductionBatchModel::where($batchNumberProdName, $productionToBakeAssembleId)
            ->orderBy('batch_number')
            ->pluck('batch_number')
            ->toArray();

        $nextBatchNumber = 1;

        foreach ($existingBatches as $batchNumber) {
            if ($batchNumber == $nextBatchNumber) {
                $nextBatchNumber++;
            } else {
                break;
            }
        }

        return $nextBatchNumber;
    }
}

