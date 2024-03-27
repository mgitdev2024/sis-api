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
            'expiration_date' => 'nullable|date',
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


            $producedItemArr = json_decode($producedItems->produced_items, true);
            $producedItemCount = count($producedItemArr) + 1;
            $addedItemCount = $producedItemCount + $primaryValue;

            $addedProducedItem = [];
            for ($producedItemCount; $producedItemCount < $addedItemCount; $producedItemCount++) {
                $itemQuantity = $secondaryValue <= $primaryPackingSize ? $secondaryValue : $primaryPackingSize;
                $itemArray = [
                    'bid' => $productionBatch->id,
                    's' => 1,
                    'q' => $itemQuantity,
                    'quality' => 'Reprocessed',
                    'parent_batch_code' => $productionBatch->batch_code,
                    'batch_code' => $productionBatch->batch_code . '-' . str_pad($producedItemCount, 3, '0', STR_PAD_LEFT) . '-R',
                ];
                $secondaryValue -= $primaryPackingSize;
                $addedProducedItem[$producedItemCount] = $itemArray;
                $producedItemArr[$producedItemCount] = $itemArray;
            }
            $producedItems->produced_items = $producedItemArr;
            $producedItems->save();

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
            $fields['expiration_date'] = $fields['expiration_date'] ?? $productionToBakeAssemble->expected_expiration_date;
            $itemCode = $productionToBakeAssemble->item_code;
            $deliveryType = $productionToBakeAssemble->delivery_type;
            $batchNumber = count(ProductionBatchModel::where($batchNumberProdName, $productionToBakeAssemble->id)->get()) + 1;
            $batchCode = ProductionBatchModel::generateBatchCode($itemCode, $deliveryType, $batchNumber);
            $productionBatch = new ProductionBatchModel();
            $productionBatch->fill($fields);
            $productionBatch->batch_code = $batchCode;
            $productionBatch->batch_number = $batchNumber;
            $productionBatch->save();

            $itemName = ItemMasterdataModel::where('item_code', $itemCode)->first();

            $data = [
                'item_name' => $itemName->description,
                'production_batch' => $productionBatch,
                'production_item' => $this->onGenerateProducedItems($productionBatch)->produced_items
            ];
            return $data;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }

    public function onGenerateProducedItems($productionBatch)
    {
        try {
            return $this->onInitialProducedItems($productionBatch);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }

    public function onInitialProducedItems($productionBatch)
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
            for ($i = 1; $i <= $primaryValue; $i++) {
                $itemQuantity = $secondaryValue <= $primaryPackingSize ? $secondaryValue : $primaryPackingSize;
                $itemArray = [
                    'bid' => $productionBatch->id,
                    'q' => $itemQuantity,
                    'status' => 1,
                    'quality' => 'Fresh',
                    'parent_batch_code' => $productionBatch->batch_code,
                    'batch_code' => $productionBatch->batch_code . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
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
            'expiration_date' => 'required|date',
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
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProductionBatchModel::class, $id, 'Production Batches');
    }
    public function onGetCurrent($id = null)
    {
        $whereFields = [
            'id' => $id,
        ];
        $withFields = ['producedItem'];
        return $this->readCurrentRecord(ProductionBatchModel::class, $id, $whereFields, $withFields, 'Production Batches');
    }
}

