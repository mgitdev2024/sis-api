<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
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
    use CrudOperationsTrait;
    use ResponseTrait;
    public static function onGetRules()
    {
        return [
            'production_batch_id' => 'nullable|integer|exists:production_batch,id',
            'production_ota_id' => 'nullable|exists:production_ota,id',
            'production_otb_id' => 'nullable|exists:production_otb,id',
            'batch_type' => 'required|integer|in:0,1',
            'quantity' => 'required',
            'chilled_exp_date' => 'nullable|date',
            'frozen_exp_date' => 'nullable|date',
            'created_by_id' => 'required|exists:credentials,id',
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->onGetRules());
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

            $itemMasterdata = ItemMasterdataModel::where('item_code', $productionToBakeAssemble->item_code)->first();
            $primaryPackingSize = intval($itemMasterdata->primary_item_packing_size);
            $producedItems = ProducedItemModel::where('production_batch_id', $productionBatch->id)->first();

            $quantity = json_decode($fields['quantity'], true);
            $data = $quantity;
            $keys = array_keys($data);
            $primaryValue = intval($quantity[$keys[0]]) ?? 0;
            $secondaryValue = intval($quantity[$keys[1]]) ?? 0;

            $stickerMultiplier = $productionBatch->productionOtb ?
                $productionBatch->productionOtb->itemMasterData->itemClassification->sticker_multiplier :
                ($productionBatch->productionOta ?
                    $productionBatch->productionOta->itemMasterData->itemClassification->sticker_multiplier :
                    1);

            $producedItemArr = json_decode($producedItems->produced_items, true);
            $producedItemCount = count($producedItemArr) + 1;
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
                ];
                $secondaryValue -= $primaryPackingSize;
                $addedProducedItem[$producedItemCount] = $itemArray;
                $producedItemArr[$producedItemCount] = $itemArray;
            }
            $producedItems->produced_items = $producedItemArr;
            $producedItems->save();

            $productionBatchCurrent = json_decode($productionBatch->quantity, true);
            $toBeAddedQuantity = json_decode($fields['quantity'], true);

            foreach ($toBeAddedQuantity as $key => $value) {
                $productionBatchCurrent[$key] = $productionBatchCurrent[$key] + $value;
            }
            $productionBatch->quantity = json_encode($productionBatchCurrent);
            $productionBatch->save();

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

            $data = [
                'item_name' => $itemName->description,
                'production_batch' => $productionBatch,
                'production_item' => $this->onGenerateProducedItems($productionBatch, $fields['batch_type'])->produced_items
            ];
            return $data;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }

    public function onGenerateProducedItems($productionBatch, $batchType)
    {
        try {
            return $this->onInitialProducedItems($productionBatch, $batchType);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }

    public function onInitialProducedItems($productionBatch, $batchType)
    {
        try {

            $quantity = json_decode($productionBatch->quantity, true);
            $data = $quantity;
            $keys = array_keys($data);
            $primaryValue = intval($quantity[$keys[0]]) ?? 0;
            $secondaryValue = intval($quantity[$keys[1]]) ?? 0;

            $productionToBakeAssemble = $productionBatch->production_otb_id
                ? ProductionOTBModel::find($productionBatch->production_otb_id)
                : ProductionOTAModel::find($productionBatch->production_ota_id);

            $itemMasterdata = ItemMasterdataModel::where('item_code', $productionToBakeAssemble->item_code)->first();
            $primaryPackingSize = intval($itemMasterdata->primary_item_packing_size);

            $producedItems = new ProducedItemModel();
            $producedItems->production_batch_id = $productionBatch->id;
            $producedItems->created_by_id = $productionBatch->created_by_id;

            $producedItemsArray = [];
            $stickerMultiplier = $productionBatch->productionOtb ?
                $productionBatch->productionOtb->itemMasterData->itemClassification->sticker_multiplier :
                ($productionBatch->productionOta ?
                    $productionBatch->productionOta->itemMasterData->itemClassification->sticker_multiplier :
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
                ];
                $secondaryValue -= $primaryPackingSize;
                $producedItemsArray[$i] = $itemArray;
            }
            $producedItems->produced_items = json_encode($producedItemsArray);
            $producedItems->save();
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
    public function onChangeStatus($id, Request $request)
    {
        $fields = $request->validate([
            'is_release' => 'required|boolean'
        ]);
        try {
            $producedItem = ProducedItemModel::where('production_batch_id', $id)->first();
            $productionBatch = $producedItem->productionBatch;

            if ($productionBatch) {
                DB::beginTransaction();
                $response = null;
                if ($fields['is_release']) {
                    $response = $this->onReleaseHoldStatus($producedItem, $productionBatch);
                } else {
                    $response = $this->onHoldStatus($producedItem, $productionBatch);
                }

                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $response);
            }
            return $this->dataResponse('error', 200, ProductionBatchModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onHoldStatus($producedItem, $productionBatch)
    {
        try {
            $producedItemArray = json_decode($producedItem->produced_items);
            foreach ($producedItemArray as $value) {
                if ($value->sticker_status === 1) {
                    if ($value->status !== 1) {
                        $value->prev_status = $value->status;
                    }
                    $value->status = 1;
                }
            }
            $productionBatch->status = 1;
            $productionBatch->update();
            $producedItem->produced_items = json_encode($producedItemArray);
            $producedItem->update();
            $response = [
                'status' => $productionBatch->statusLabel
            ];
            return $response;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }

    }

    public function onReleaseHoldStatus($producedItem, $productionBatch)
    {
        try {
            $producedItemArray = json_decode($producedItem->produced_items);
            foreach ($producedItemArray as $value) {
                if ($value->sticker_status === 1) {
                    $value->status = $value->prev_status;
                }
            }

            $productionBatch->status = 0;
            if ($productionBatch->productionOrder->status === 1) {
                $productionBatch->status = 2;
            }

            $productionBatch->update();
            $producedItem->produced_items = json_encode($producedItemArray);
            $producedItem->update();
            $response = [
                'status' => $productionBatch->statusLabel
            ];
            return $response;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }

    }
    public function onGetCurrent($id = null, $order_type = null)
    {
        $whereFields = [];
        if (strcasecmp($order_type, 'otb') === 0) {
            $whereFields['production_otb_id'] = $id;
        } else {
            $whereFields['production_ota_id'] = $id;
        }
        $withFields = ['producedItem'];
        return $this->readCurrentRecord(ProductionBatchModel::class, $id, $whereFields, $withFields, null, 'Production Batches');
    }
}

