<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\ForStockTransfer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\QualityAssurance\SubStandardItemController;
use App\Http\Controllers\v1\WMS\Storage\QueuedSubLocationController;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\InventoryKeeping\ForStockTransfer\StockRequestForTransferModel;
use App\Models\WMS\InventoryKeeping\StockTransferItemModel;
use App\Models\WMS\InventoryKeeping\StockTransferListModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Traits\WMS\QueueSubLocationTrait;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class StockRequestForTransferController extends Controller
{
    use WmsCrudOperationsTrait, QueueSubLocationTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'stock_transfer_item_id' => 'required|integer|exists:wms_stock_transfer_items,id',
            'sub_location_id' => 'required|integer|exists:wms_storage_sub_locations,id',
            'layer_level' => 'required|integer',
            'created_by_id' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $data = [];
            $stockTransferItemModel = StockTransferItemModel::find($fields['stock_transfer_item_id']);
            if ($stockTransferItemModel) {
                $permanentSubLocation = SubLocationModel::where('is_permanent', 1)
                    ->where('id', $fields['sub_location_id'])
                    ->first();
                if (!$permanentSubLocation) {
                    $message = [
                        'error_type' => 'incorrect_storage',
                        'message' => 'Sub Location does not exist or incorrect storage type'
                    ];
                    $data['sub_location_error_message'] = $message;
                    return $this->dataResponse('success', 200, __('msg.create_failed'), $data);
                }

                $itemMasterdata = $stockTransferItemModel->itemMasterdata;
                $isStorageTypeMismatch = !($permanentSubLocation->zone->storage_type_id === $itemMasterdata->storage_type_id);
                if ($isStorageTypeMismatch) {
                    $message = [
                        'error_type' => 'storage_mismatch',
                        'storage_type' => $itemMasterdata->storage_type_label['long_name']
                    ];
                    $data['sub_location_error_message'] = $message;
                    return $this->dataResponse('success', 200, __('msg.create_failed'), $data);
                }

                $queuedSubLocationAvailability = $this->onCheckAvailability($permanentSubLocation->id, true, $fields['layer_level'], $fields['created_by_id']);
                if ($queuedSubLocationAvailability) {
                    $message = [
                        'error_type' => 'storage_occupied',
                        'message' => SubLocationModel::onGenerateStorageCode($permanentSubLocation->id, $fields['layer_level'])['storage_code'] . ' is in use.'
                    ];
                    $data['sub_location_error_message'] = $message;
                    return $this->dataResponse('success', 200, __('msg.create_failed'), $data);

                }
                $checkStorageSpace = $this->onCheckStorageSpace($permanentSubLocation->id, $fields['layer_level'], 1);
                $isStorageFull = !$checkStorageSpace['is_full'];
                if ($isStorageFull) {
                    $message = [
                        'error_type' => 'storage_full',
                        'current_size' => $checkStorageSpace['current_size'],
                        'allocated_space' => $checkStorageSpace['allocated_space'],
                        'remaining_space' => $checkStorageSpace['remaining_space']
                    ];
                    $data['sub_location_error_message'] = $message;
                    return $this->dataResponse('success', 200, __('msg.create_failed'), $data);
                }

                $stockRequestForTransferModel = new StockRequestForTransferModel();
                $stockRequestForTransferModel->stock_transfer_list_id = $stockTransferItemModel->stockTransferList->id;
                $stockRequestForTransferModel->stock_transfer_item_id = $fields['stock_transfer_item_id'];
                $stockRequestForTransferModel->sub_location_id = $permanentSubLocation->id;
                $stockRequestForTransferModel->layer_level = $fields['layer_level'];
                $stockRequestForTransferModel->created_by_id = $fields['created_by_id'];
                $stockRequestForTransferModel->save();
                DB::commit();

                return $this->dataResponse('success', 200, __('msg.create_success'));

            }

            return $this->dataResponse('success', 200, 'Stock Request For Transfer ' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Stock Request For Transfer ' . __('msg.create_failed'));
        }
    }

    public function onUpdate(Request $request, $stock_transfer_item_id)
    {
        $fields = $request->validate([
            'updated_by_id' => 'required',
            'scanned_items' => 'required|json',
        ]);
        try {
            $stockRequestForTransferModel = StockRequestForTransferModel::where([
                'stock_transfer_item_id' => $stock_transfer_item_id,
                'status' => 1
            ])->orderBy('id', 'DESC')->first();

            if ($stockRequestForTransferModel) {
                DB::beginTransaction();
                $updateById = $fields['updated_by_id'];
                $scannedItems = json_decode($fields['scanned_items'], true);
                $forTransferItems = [];
                foreach ($scannedItems as $itemValue) {
                    $productionItemModel = ProductionItemModel::where('production_batch_id', $itemValue['bid'])->first();
                    $productionItems = json_decode($productionItemModel->produced_items, true);
                    if ($productionItems[$itemValue['sticker_no']]['status'] == 14) {
                        $forTransferItems[] = $itemValue;
                    }
                }

                if (count($forTransferItems) > 0) {
                    // STOCK REQUEST FOR TRANSFER UPDATE
                    $stockRequestForTransferModel->scanned_items = json_encode($forTransferItems);
                    $stockRequestForTransferModel->updated_by_id = $fields['updated_by_id'];
                    $stockRequestForTransferModel->save();
                    DB::commit();
                    return $this->dataResponse('success', 200, __('msg.update_success'));
                }

            }
            return $this->dataResponse('success', 200, __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onTransferItems(Request $request, $stock_transfer_item_id)
    {
        $fields = $request->validate([
            'updated_by_id' => 'required',
            'scanned_items' => 'required|json',
        ]);

        try {
            $stockRequestForTransferModel = StockRequestForTransferModel::where([
                'stock_transfer_item_id' => $stock_transfer_item_id,
                'status' => 1
            ])->first();
            if ($stockRequestForTransferModel && $stockRequestForTransferModel->sub_location_id) {
                DB::beginTransaction();

                $updatedById = $fields['updated_by_id'];
                $subLocationId = $stockRequestForTransferModel->sub_location_id;
                $layerLevel = $stockRequestForTransferModel->layer_level;
                $stockRequestForTransferModelProductionItems = json_decode($stockRequestForTransferModel->stockTransferItem->selected_items, true);
                $scannedItems = json_decode($fields['scanned_items'], true);

                $this->onQueueSubLocation($updatedById, $scannedItems, $stockRequestForTransferModelProductionItems, $subLocationId, $layerLevel, $stockRequestForTransferModel->stockTransferList->reference_number);
                $this->onUpdateStockRequestTransfer($stockRequestForTransferModel, $stockRequestForTransferModel->stockTransferItem, $scannedItems, $updatedById);
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.create_success'));
            } else {
                return $this->dataResponse('success', 200, __('msg.record_not_found'));
            }

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onSubstandardItems(Request $request, $stock_transfer_item_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'scanned_items' => 'required',
            'reason' => 'required',
            'attachment' => 'nullable',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $scannedItems = json_decode($fields['scanned_items'], true);
            $reason = $fields['reason'];
            $attachment = $fields['attachment'] ?? null;
            $locationId = 4; // Warehouse Transfer

            // Checking of Scanned For Transfer items, will be removed when it is also scanned for substandard
            $matchedScannedItemSubstandard = [];
            $stockRequestTransferModel = StockRequestForTransferModel::where('stock_transfer_item_id', $stock_transfer_item_id)->first();
            if ($stockRequestTransferModel) {

                $transferItems = json_decode($stockRequestTransferModel->scanned_items, true);
                $filteredArr = array_filter($transferItems, function ($item1) use ($scannedItems, &$matchedScannedItemSubstandard) {
                    foreach ($scannedItems as $item2) {
                        if ($item1['bid'] == $item2['bid'] && $item1['sticker_no'] == $item2['sticker_no']) {
                            $matchedScannedItemSubstandard[] = $item1;
                            return false;
                        }
                    }
                    return true;
                });
                $transferItems = $filteredArr;
                $stockRequestTransferModel->scanned_items = json_encode(array_values($transferItems));
                $stockRequestTransferModel->save();
            }

            $substandardItems = [];
            foreach ($scannedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionItem = $productionBatch->productionItems;
                $inclusionArray = ['14']; // Stock Transfer
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                if ($flag) {
                    $substandardItems[] = $itemDetails;
                }
            }
            $stockTransferItemModel = StockTransferItemModel::find($stock_transfer_item_id);
            $existingSubstandardItems = json_decode($stockTransferItemModel->substandard_items, true) ?? [];
            $stockTransferItemModel->substandard_items = json_encode(array_merge($existingSubstandardItems, $substandardItems));
            $substandardController = new SubStandardItemController();
            $substandardRequest = new Request([
                'created_by_id' => $createdById,
                'scanned_items' => $fields['scanned_items'],
                'reason' => $reason,
                'attachment' => $attachment,
                'location_id' => $locationId,
            ]);
            $substandardController->onCreate($substandardRequest);
            dd($substandardController);

            $stockRequestTransfer = new StockRequestForTransferController();
            $stockRequestTransferRequest = new Request([
                'updated_by_id' => $createdById,
                'scanned_items' => $stockRequestTransferModel->scanned_items,
            ]);
            $stockRequestTransfer->onTransferItems($stockRequestTransferRequest, $stock_transfer_item_id);
            DB::commit();
            return $this->dataResponse('success', 201, 'Sub-Standard ' . __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollback();
            dd($exception);
            return $this->dataResponse('error', 400, 'Sub-Standard ' . __('msg.create_failed'));
        }
    }

    public function onUpdateStockRequestTransfer($stockRequestTransferModel, $stockTransferItemModel, $scannedItems, $updateById)
    {
        try {
            $stockRequestTransferModel->delete();
            $transferQuantityCount = $stockTransferItemModel->transfer_quantity;
            $existingTransferredItems = json_decode($stockTransferItemModel->transferred_items, true) ?? [];
            $mergedTransferredItems = array_merge($existingTransferredItems, $scannedItems);

            $scannedItemsCount = count($scannedItems) + count($existingTransferredItems);

            if ($transferQuantityCount <= $scannedItemsCount) {
                $stockTransferItemModel->status = 2;
                $stockTransferItemModel->updated_by_id = $updateById;


                $stockTransferListModel = $stockTransferItemModel->stockTransferList;
                $stockTransferListModel->status = 2;
                $stockTransferListModel->updated_by_id = $updateById;
                $stockTransferListModel->save();
                $this->createWarehouseLog(null, null, StockTransferListModel::class, $stockTransferListModel->id, $stockTransferListModel->getAttributes(), $updateById, 1);
            }

            $stockTransferItemModel->transferred_items = json_encode($mergedTransferredItems);
            $stockTransferItemModel->save();
            $this->createWarehouseLog(null, null, StockTransferItemModel::class, $stockTransferItemModel->id, $stockTransferItemModel->getAttributes(), $updateById, 1);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onItemCheckHoldInactiveDone($producedItems, $itemKey, $inclusionArray, $exclusionArray)
    {
        $inArrayFlag = count($inclusionArray) > 0 ?
            in_array($producedItems[$itemKey]['status'], $inclusionArray) :
            !in_array($producedItems[$itemKey]['status'], $exclusionArray);
        return $producedItems[$itemKey]['sticker_status'] != 0 && $inArrayFlag;
    }

    public function onQueueSubLocation($createdById, $scannedItems, $stockRequestForTransferModelProductionItems, $subLocationId, $layerLevel, $referenceNumber)
    {
        try {
            $itemsPerBatchArr = [];
            foreach ($scannedItems as $scannedValue) {
                $stickerNumber = $scannedValue['sticker_no'];
                $batchId = $scannedValue['bid'];

                $flag = $this->onCheckScannedItems($stockRequestForTransferModelProductionItems, $stickerNumber, $batchId);

                if ($flag) {
                    $itemsPerBatchArr[$batchId][] = $scannedValue;
                }
            }
            if (count($itemsPerBatchArr) > 0) {
                foreach ($itemsPerBatchArr as $key => $itemValue) {
                    $productionId = ProductionItemModel::where('production_batch_id', $key)->pluck('id')->first();
                    $this->onQueueStorage($createdById, $itemValue, $subLocationId, true, $layerLevel, ProductionItemModel::class, $productionId, $referenceNumber);
                }
            }

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }


    public function onCheckScannedItems($scannedItemsBasis, $stickerNumber, $batchId)
    {
        try {
            foreach ($scannedItemsBasis as $value) {
                if (($value['sticker_no'] == $stickerNumber) && $value['bid'] == $batchId) {
                    $productionItems = ProductionItemModel::where('production_batch_id', $batchId)->first();
                    $item = json_decode($productionItems->produced_items, true)[$stickerNumber];
                    if ($item['status'] == '14') {
                        return true;
                    }
                }
            }

            return false;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());

        }
    }

    public function onDelete($stock_transfer_item_id)
    {
        try {
            // $stockRequestForTransferModel = StockRequestForTransferModel::where([
            //     'stock_transfer_item_id' => $stock_transfer_item_id,
            // ])->orderBy('id', 'DESC')->first();
            // $scannedItems = json_decode($stockRequestForTransferModel->scanned_items, true);
            // foreach ($scannedItems as $itemValue) {
            //     $productionItemModel = ProductionItemModel::where('production_batch_id', $itemValue['bid'])->first();
            //     $productionItems = json_decode($productionItemModel->produced_items, true);
            //     $productionItems[$itemValue['sticker_no']]['status'] = 14;
            //     $productionItemModel->produced_items = json_encode($productionItems);
            //     $productionItemModel->save();
            // }
            StockRequestForTransferModel::where([
                'stock_transfer_item_id' => $stock_transfer_item_id,
            ])->delete(); // Delete all instances
            return $this->dataResponse('success', 200, __('msg.delete_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.delete_failed'), $exception->getMessage());
        }
    }

}
