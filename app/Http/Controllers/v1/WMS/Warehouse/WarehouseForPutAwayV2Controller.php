<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Storage\StockLogModel;
use App\Models\WMS\Warehouse\WarehouseForPutAwayV2Model;
use App\Models\WMS\Warehouse\WarehousePutAwayModel;
use App\Traits\WMS\QueueSubLocationTrait;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use Exception;
use DB;
class WarehouseForPutAwayV2Controller extends Controller
{
    use WmsCrudOperationsTrait, QueueSubLocationTrait;

    #region CRUD operations cuuhhhhhh, might wanna check this out if you new
    public function onCreateSingleTransaction(Request $request)
    {
        $fields = $request->validate([
            'warehouse_put_away_key' => 'required|string',
            'sub_location_id' => 'required|exists:wms_storage_sub_locations,id',
            'layer_level' => 'required|integer',
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $explodeWarehousePutAwayKey = explode('-', $fields['warehouse_put_away_key']);
            $warehouseReceivingReferenceNumber = $explodeWarehousePutAwayKey[0];
            $itemId = $explodeWarehousePutAwayKey[1];
            $temporaryStorageId = $explodeWarehousePutAwayKey[2] ?? null;
            $data = [];

            // Check if put away key exists
            $warehousePutAwayModel = WarehousePutAwayModel::where([
                'warehouse_receiving_reference_number' => $warehouseReceivingReferenceNumber,
                'item_id' => $itemId,
            ]);
            if ($temporaryStorageId != null) {
                $warehousePutAwayModel->where('temporary_storage_id', $temporaryStorageId);
            }
            $warehousePutAwayModel = $warehousePutAwayModel->exists();
            if (!$warehousePutAwayModel) {
                $message = [
                    'error_type' => 'put_away_does_not_exist',
                    'message' => 'The put away does not exist'
                ];
                $data['sub_location_error_message'] = $message;
                return $this->dataResponse('success', 200, __('msg.update_failed'), $data);
            }
            $itemMasterdata = ItemMasterdataModel::where('id', $itemId)->first();
            $subLocationModel = SubLocationModel::where([
                'id' => $fields['sub_location_id'],
                'is_permanent' => 1,
            ])->first();

            // Sub Location Does Not Exist
            if (!$subLocationModel) {
                $message = [
                    'error_type' => 'incorrect_storage',
                    'message' => 'Sub Location does not exist or incorrect storage type'
                ];
                $data['sub_location_error_message'] = $message;
                return $this->dataResponse('success', 200, __('msg.update_failed'), $data);
            }
            // Storage Mismatch
            $isStorageTypeMismatch = !($subLocationModel->zone->storage_type_id == $itemMasterdata->storage_type_id);
            if ($isStorageTypeMismatch) {
                $message = [
                    'error_type' => 'storage_mismatch',
                    'storage_type' => $itemMasterdata->storage_type_label['long_name']
                ];
                $data['sub_location_error_message'] = $message;
                return $this->dataResponse('success', 200, __('msg.update_failed'), $data);
            }

            // Check Storage Space
            $checkStorageSpace = $this->onCheckStorageSpace($subLocationModel->id, $fields['layer_level'], 1);
            $isStorageFull = !$checkStorageSpace['is_full'];
            if ($isStorageFull) {
                $message = [
                    'error_type' => 'storage_full',
                    'current_size' => $checkStorageSpace['current_size'],
                    'allocated_space' => $checkStorageSpace['allocated_space'],
                    'remaining_space' => $checkStorageSpace['remaining_space']
                ];
                $data['sub_location_error_message'] = $message;
                return $this->dataResponse('success', 200, __('msg.update_failed'), $data);
            }

            // Create Warehouse For Put Away
            $warehouseForPutAway = new WarehouseForPutAwayV2Model();
            $warehouseForPutAway->warehouse_put_away_key = $fields['warehouse_put_away_key'];
            $warehouseForPutAway->warehouse_receiving_reference_number = $warehouseReceivingReferenceNumber;
            $warehouseForPutAway->item_id = $itemId;
            $warehouseForPutAway->temporary_storage_id = $temporaryStorageId;
            $warehouseForPutAway->sub_location_id = $fields['sub_location_id'];
            $warehouseForPutAway->layer_level = $fields['layer_level'];
            $warehouseForPutAway->created_by_id = $fields['created_by_id'];
            $warehouseForPutAway->save();
            DB::commit();
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Warehouse For Put Away ' . __('msg.create_failed'));
        }
    }

    public function onUpdateSingleTransaction(Request $request, $put_away_key)
    {
        $fields = $request->validate([
            'production_items' => 'required',
            'action' => 'required|in:0,1', // 0 = update, 1 = transfer
            'is_storage_full' => 'nullable|in:1', // 1 = true
            'created_by_id' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $action = $fields['action'];
            $warehouseForPutAway = WarehouseForPutAwayV2Model::where('warehouse_put_away_key', $put_away_key)->first();
            if (!$warehouseForPutAway) {
                return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.record_not_found'));
            }
            switch ($action) {
                case 0:
                    $scannedItems = json_decode($fields['production_items'], true);
                    // Update status = 3.1
                    foreach ($scannedItems as $items) {
                        $this->onUpdateItemStatus($items, 3.1, 3);
                    }
                    $warehouseForPutAway->production_items = json_encode($scannedItems);
                    $warehouseForPutAway->save();
                    break;

                case 1:
                    $scannedItems = json_decode($fields['production_items'], true);

                    if ($fields['is_storage_full'] == 1) {
                        foreach ($scannedItems as $items) {
                            $this->onUpdateItemStatus($items, 3.1, 3);
                        }
                        $warehouseForPutAway->production_items = json_encode($scannedItems);
                        $warehouseForPutAway->save();
                    }
                    // update WarehouseForPutAwayV2 status = 0 and transferreditems
                    $this->onStoreSingleTransaction($warehouseForPutAway, $scannedItems, $fields['created_by_id']); // Store transaction status = 13
                    break;
            }


            DB::commit();
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Warehouse For Put Away ' . __('msg.update_failed'));
        }
    }

    public function onDeleteSingleTransaction($put_away_key)
    {
        try {
            DB::beginTransaction();
            $warehouseForPutAway = WarehouseForPutAwayV2Model::where('warehouse_put_away_key', $put_away_key)->first();
            if ($warehouseForPutAway) {
                $isStored = $warehouseForPutAway->status == 0; // 0 = stored, 1 = on going
                if ($isStored) {
                    $warehouseForPutAway->delete();
                } else {
                    $productionItems = json_decode($warehouseForPutAway->production_items, true);
                    foreach ($productionItems as $items) {
                        $this->onUpdateItemStatus($items, 3, null);
                    }
                    $warehouseForPutAway->delete();

                }
                DB::commit();
                return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.delete_success'));
            }
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.record_not_found'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Warehouse For Put Away ' . __('msg.delete_failed'));
        }
    }

    public function onGet($put_away_key, $created_by_id)
    {
        try {
            $warehouseForPutAwayV2Model = WarehouseForPutAwayV2Model::where([
                'warehouse_put_away_key' => $put_away_key,
                'created_by_id' => $created_by_id,
            ])->first();

            if ($warehouseForPutAwayV2Model) {
                $productionItems = json_decode($warehouseForPutAwayV2Model->production_items, true);

                $data = [];
                foreach ($productionItems as $items) {
                    $productionBatchModel = ProductionBatchModel::find($items['bid']);
                    $itemMasterdataModel = $productionBatchModel->itemMasterdata;
                    $itemCode = $itemMasterdataModel->item_code;
                    $itemId = $itemMasterdataModel->id;
                    $productionItemModel = ProductionItemModel::where('production_batch_id', $productionBatchModel->id)->first();
                    $producedItems = json_decode($productionItemModel->produced_items, true);
                    $batchCode = $producedItems[$items['sticker_no']]['batch_code'];

                    $data[] = [
                        'bid' => $productionBatchModel->id,
                        'q' => $producedItems[$items['sticker_no']]['q'],
                        'sticker_no' => $items['sticker_no'],
                        'item_code' => $itemCode,
                        'item_id' => $itemId,
                        'batch_code' => $batchCode
                    ];
                }
                return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.record_found'), $data);

            }
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.record_not_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.record_not_found'));

        }
    }
    #endregion

    #region New Put Away Functions
    public function onStoreSingleTransaction($warehouseForPutAwayV2Model, $itemsToTransfer, $createdById)
    {
        try {
            $warehouseReceivingReferenceNumber = $warehouseForPutAwayV2Model->warehouse_receiving_reference_number;
            $itemId = $warehouseForPutAwayV2Model->item_id;
            $itemMasterdataModel = ItemMasterdataModel::find($itemId);
            $primaryUom = $itemMasterdataModel->Uom->long_name;
            $primaryPackingSize = $itemMasterdataModel->primary_item_packing_size;


            $temporaryStorageId = $warehouseForPutAwayV2Model->temporary_storage_id ?? null;
            $subLocationId = $warehouseForPutAwayV2Model->sub_location_id;
            $layerLevel = $warehouseForPutAwayV2Model->layer_level;


            // Warehouse Put Away Update
            $warehousePutAwayModel = WarehousePutAwayModel::where([
                'warehouse_receiving_reference_number' => $warehouseReceivingReferenceNumber,
                'item_id' => $itemId,
            ]);
            if ($temporaryStorageId != null) {
                $warehousePutAwayModel->where('temporary_storage_id', $temporaryStorageId);
            }
            $warehousePutAwayModel = $warehousePutAwayModel->first();

            $remainingPieces = 0;
            $remainingQuantity = json_decode($warehousePutAwayModel->remaining_quantity, true);
            $discrepancyData = json_decode($warehousePutAwayModel->discrepancy_data, true);
            $discrepancyArrConstructed = [];
            $transferredQuantity = json_decode($warehousePutAwayModel->transferred_quantity, true);

            foreach ($discrepancyData as $discrepancyItem) {
                $batchId = $discrepancyItem['bid'];
                $stickerNo = $discrepancyItem['sticker_no'];
                $discrepancyArrConstructed["$batchId-$stickerNo"] = $discrepancyItem;
            }
            foreach ($itemsToTransfer as $items) {
                $batchId = $items['bid'];
                $stickerNo = $items['sticker_no'];
                $discrepancyKey = "$batchId-$stickerNo";
                if (array_key_exists($discrepancyKey, $discrepancyArrConstructed)) {
                    unset($discrepancyArrConstructed[$discrepancyKey]);
                }
                $remainingPieces += $items['q'];

                if (!isset($remainingQuantity[$primaryUom])) {
                    $remainingQuantity[$primaryUom] = 0;
                }
                if (!isset($transferredQuantity[$primaryUom])) {
                    $transferredQuantity[$primaryUom] = 0;
                }
                if ($remainingPieces >= $primaryPackingSize || $transferredQuantity >= $primaryPackingSize) {
                    if ($transferredQuantity >= $primaryPackingSize) {
                        $transferredQuantity[$primaryUom]++;
                    }
                    if ($remainingPieces >= $primaryPackingSize) {
                        $remainingQuantity[$primaryUom]--;
                    }
                    $remainingPieces -= $primaryPackingSize;
                }
            }
            $warehousePutAwayModel->remaining_quantity = json_encode($remainingQuantity);
            $warehousePutAwayModel->transferred_quantity = json_encode($transferredQuantity);
            $warehousePutAwayModel->discrepancy_data = json_encode(array_values($discrepancyArrConstructed));
            $warehousePutAwayModel->save();

            $warehouseForPutAwayV2Model->status = 0; // Status transferred ready for deletion in queueing process cuhzx
            $warehouseForPutAwayV2Model->save();
            $warehouseForPutAwayItems = json_decode($warehouseForPutAwayV2Model->production_items, true);
            $this->onQueueSubLocation($createdById, $itemsToTransfer, $warehouseForPutAwayItems, $subLocationId, $layerLevel, $warehouseReceivingReferenceNumber);
            // Delete For Put Away
            $this->onDeleteSingleTransaction($warehouseForPutAwayV2Model->warehouse_put_away_key);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }


    public function onUpdateItemStatus($items, $status, $validation)
    {
        try {
            $productionBatchId = $items['bid'];
            $stickerNo = $items['sticker_no'];

            $productionItemModel = ProductionItemModel::where('production_batch_id', $productionBatchId)->first();
            $producedItems = json_decode($productionItemModel->produced_items, true);
            if ($validation == null) {
                $producedItems[$stickerNo]['status'] = $status;
                $productionItemModel->produced_items = json_encode($producedItems);
                $productionItemModel->save();
            } else if ($producedItems[$stickerNo]['status'] == $validation) {
                $producedItems[$stickerNo]['status'] = $status;
                $productionItemModel->produced_items = json_encode($producedItems);
                $productionItemModel->save();
            }

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onQueueSubLocation($createdById, $scannedItems, $warehouseForPutAwayItems, $subLocationId, $layerLevel, $referenceNumber)
    {
        try {
            $itemsPerBatchArr = [];
            foreach ($scannedItems as $scannedValue) {
                $stickerNumber = $scannedValue['sticker_no'];
                $batchId = $scannedValue['bid'];

                $flag = $this->onCheckScannedItems($warehouseForPutAwayItems, $stickerNumber, $batchId);

                if ($flag) {
                    $itemsPerBatchArr[$batchId][] = $scannedValue;
                }
            }

            if (count($itemsPerBatchArr) > 0) {
                $latestStockTransactionNumber = StockLogModel::onGetCurrentTransactionNumber() + 1;
                foreach ($itemsPerBatchArr as $key => $itemValue) {
                    $productionId = ProductionItemModel::where('production_batch_id', $key)->pluck('id')->first();
                    $this->onQueueStorage(
                        $createdById,
                        $itemValue,
                        $subLocationId,
                        true,
                        $layerLevel,
                        ProductionItemModel::class,
                        $productionId,
                        $referenceNumber,
                        1,
                        $latestStockTransactionNumber
                    );
                }
            }

        } catch (Exception $exception) {
            dd($exception);
            throw new Exception($exception->getMessage());
        }
    }


    public function onCheckScannedItems($itemsToTransfer, $stickerNumber, $batchId)
    {
        try {
            foreach ($itemsToTransfer as $value) {
                if (($value['sticker_no'] == $stickerNumber) && $value['bid'] == $batchId) {
                    $productionItems = ProductionItemModel::where('production_batch_id', $batchId)->first();
                    $item = json_decode($productionItems->produced_items, true)[$stickerNumber];
                    if ($item['status'] == '3.1') {
                        return true;
                    }
                }
            }

            return false;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
    #endregion
}
