<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Storage\StockLogModel;
use App\Models\WMS\Warehouse\WarehousePutAwayModel;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use DB;
use Exception;
class WarehouseBulkPutAwayController extends Controller
{
    use QueueSubLocationTrait;
    public function onGetTemporaryStorageItems($sub_location_id, $status, $storageType)
    {
        try {
            $isMatch = true;
            $items = $this->onGetQueuedItems($sub_location_id, false);
            $combinedItems = array_merge(...$items);
            $data = [];
            $currentItemStatus = null;
            foreach ($combinedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $itemId = $productionOrderToMake->itemMasterdata->id;
                $storageStorageTypeId = $productionOrderToMake->itemMasterdata->storage_type_id;
                $stickerNumber = $itemDetails['sticker_no'];
                $producedItem = json_decode($productionBatch->productionItems->produced_items, true)[$stickerNumber];

                $currentItemStatus = $producedItem['status'];
                if ($producedItem['status'] != $status || $storageStorageTypeId != $storageType) {
                    $isMatch = false;
                    break;
                }
                $warehouseReceivingReferenceNumber = $producedItem['warehouse']['warehouse_receiving']['reference_number'];
                $warehousePutAwayModel = WarehousePutAwayModel::where([
                    'warehouse_receiving_reference_number' => $warehouseReceivingReferenceNumber,
                    'item_id' => $itemId,
                    'temporary_storage_id' => $sub_location_id,
                ])->get();

                $receivedQuantity = 0;
                $transferredQuantity = 0;
                foreach ($warehousePutAwayModel as $putAway) {
                    $receivedQuantity += array_values(json_decode($putAway['received_quantity'], true))[0];
                    $transferredQuantity += array_values(json_decode($putAway['transferred_quantity'] ?? '[]', true))[0] ?? 0;
                }

                $warehousePutAwayKey = "$warehouseReceivingReferenceNumber-$itemId-$sub_location_id";
                if (!isset($data[$warehousePutAwayKey])) {
                    $data[$warehousePutAwayKey] = [
                        'warehouse_put_away_key' => $warehousePutAwayKey,
                        'slid' => $sub_location_id,
                        'rack_code' => SubLocationModel::find($sub_location_id)->code,
                        'received_quantity' => $receivedQuantity,
                        'transferred_quantity' => $transferredQuantity,
                        'total_item_count' => 0,
                        'production_items' => []
                    ];
                }

                if (!isset($data[$warehousePutAwayKey]['production_items'][$itemCode])) {
                    $data[$warehousePutAwayKey]['production_items'][$itemCode] = [];
                }
                $data[$warehousePutAwayKey]['production_items'][$itemCode][] = $itemDetails;
                $data[$warehousePutAwayKey]['current_item_status'] = $producedItem['status'];
                $data[$warehousePutAwayKey]['total_item_count']++;
            }
            if (!$isMatch) {
                $message = [
                    'error_type' => 'storage_type_not_matched',
                    'message' => 'Items in this storage do not match the required type'
                ];
                $data['sub_location_error_message'] = $message;
                return $this->dataResponse('error', 200, __('msg.record_not_found'), $data);
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onBulkPutAway(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'sub_location_id' => 'required|exists:wms_storage_sub_locations,id',
            'layer_level' => 'required',
            'temporary_storages' => 'required|json'
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $layerLevel = $fields['layer_level'];
            $subLocationId = $fields['sub_location_id'];
            $temporaryStorages = json_decode($fields['temporary_storages'], true);
            foreach ($temporaryStorages as $storage) {
                $storageKey = $storage['warehouse_put_away_key'];
                $warehousePutAwayKey = explode('-', $storageKey);
                $warehouseReceivingReferenceNumber = $warehousePutAwayKey[0];
                $itemId = $warehousePutAwayKey[1];
                $temporaryStorageId = $warehousePutAwayKey[2];

                // Warehouse Put Away Update
                $warehousePutAwayModel = WarehousePutAwayModel::where([
                    'warehouse_receiving_reference_number' => $warehouseReceivingReferenceNumber,
                    'item_id' => $itemId,
                    'temporary_storage_id' => $temporaryStorageId,
                ])->first();

                if ($warehousePutAwayModel) {
                    $remainingPieces = 0;
                    $remainingQuantity = json_decode($warehousePutAwayModel->remaining_quantity, true);
                    $discrepancyData = json_decode($warehousePutAwayModel->discrepancy_data, true);
                    $discrepancyArrConstructed = [];
                    $transferredQuantity = json_decode($warehousePutAwayModel->transferred_quantity, true);

                    $itemMasterdataModel = ItemMasterdataModel::find($itemId);
                    $primaryUom = $itemMasterdataModel->Uom->long_name;
                    $primaryPackingSize = $itemMasterdataModel->primary_item_packing_size;


                    foreach ($discrepancyData as $discrepancyItem) {
                        $batchId = $discrepancyItem['bid'];
                        $stickerNo = $discrepancyItem['sticker_no'];
                        $discrepancyArrConstructed["$batchId-$stickerNo"] = $discrepancyItem;
                    }

                    $itemsToTransfer = $storage['production_items'];
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

                    $warehouseForPutAwayItems = $itemsToTransfer;
                    $this->onQueueSubLocation($createdById, $itemsToTransfer, $warehouseForPutAwayItems, $subLocationId, $layerLevel, $warehouseReceivingReferenceNumber);
                }
            }
            DB::commit();
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'));
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
                    if ($item['status'] == '3') {
                        return true;
                    }
                }
            }

            return false;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}


/*


                $data[] = [
                    'bid' => $itemDetails['bid'],
                    'item_code' => $itemCode,
                    'item_id' => $itemId,
                    'sticker_no' => $stickerNumber,
                    'q' => $producedItem['q'],
                    'batch_code' => $producedItem['batch_code'],
                    'parent_batch_code' => $producedItem['parent_batch_code'],
                    'slid' => $sub_location_id,
                    'rack_code' => SubLocationModel::find($sub_location_id)->code,
                    'warehouse' => [
                        'warehouse_put_away' => [
                            'reference_number' => $warehouseReceivingReferenceNumber,
                            'received_quantity' => $receivedQuantity,
                            'to_receive_quantity' => $transferredQuantity
                        ]
                    ]
                ];
*/
