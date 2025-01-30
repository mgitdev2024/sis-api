<?php

namespace App\Traits\WMS;

use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Storage\QueuedSubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use App\Models\WMS\Storage\StockInventoryModel;
use App\Models\WMS\Storage\StockLogModel;
use Exception;
use App\Traits\ResponseTrait;
use App\Traits\WMS\WarehouseLogTrait;
use App\Traits\MOS\ProductionLogTrait;
use DB;

trait QueueSubLocationTrait
{
    use ResponseTrait, WarehouseLogTrait, ProductionLogTrait;

    public function onQueueStorage($createdById, $scannedItems, $subLocationId, $isPermanent, $layerLevel = null, $entityModel = null, $entityId = null, $referenceNumber = null, $action = 1, $stockLogTransactionNumber = null)
    {
        try {
            $entityDetails = [
                'entity_model' => $entityModel,
                'entity_id' => $entityId,
            ];
            $data = null;
            DB::beginTransaction();
            if ($isPermanent) {
                $data = $this->onQueuePermanentStorage($createdById, $scannedItems, $subLocationId, $layerLevel, $entityDetails, $referenceNumber, $action, $stockLogTransactionNumber);
            } else {
                $data = $this->onQueueTemporaryStorage($createdById, $scannedItems, $subLocationId, $referenceNumber);
            }
            if ($data) {
                DB::commit();
                return $this->dataResponse('success', 201, 'Queue Storage ' . __('msg.create_success'), $data);
            }
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Queue Storage ' . __('msg.create_failed'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception);
        }
    }
    public function onQueuePermanentStorage($createdById, $scannedItems, $subLocationId, $layerLevel, $entityDetails, $referenceNumber, $action, $stockLogTransactionNumber)
    {
        try {
            $subLocation = SubLocationModel::where('id', $subLocationId)
                ->where('is_permanent', 1)
                ->firstOrFail();
            $queuedPermanentStorageModel = QueuedSubLocationModel::where([
                'sub_location_id' => $subLocationId,
                'layer_level' => $layerLevel
            ])
                ->orderBy('id', 'DESC')
                ->first();
            $layers = json_decode($subLocation->layers, true);
            $currentLayerCapacity = $layers[$layerLevel]['max'];
            $existingItemStored = [];
            if ($queuedPermanentStorageModel) {
                $existingItemStored = json_decode($queuedPermanentStorageModel->production_items, true);
                $currentLayerCapacity = $queuedPermanentStorageModel->storage_remaining_space;
            }

            $itemId = null;
            $currentScannedItems = [];
            foreach ($scannedItems as $value) {
                if ($currentLayerCapacity > 0) {
                    $itemId = $value['item_id'];
                    $currentScannedItems[] = $value;
                    --$currentLayerCapacity;

                    $this->onUpdateItemLocationLog($value['bid'], $value['sticker_no'], $subLocationId, $layerLevel, $createdById, true);
                }
            }

            $mergedItemArray = array_merge($existingItemStored, $currentScannedItems);
            $queuePermanentStorage = new QueuedSubLocationModel();
            $queuePermanentStorage->sub_location_id = $subLocationId;
            $queuePermanentStorage->layer_level = $layerLevel;
            $queuePermanentStorage->production_items = json_encode($mergedItemArray);
            $queuePermanentStorage->quantity = count($mergedItemArray);
            $queuePermanentStorage->storage_remaining_space = $currentLayerCapacity;
            $queuePermanentStorage->created_by_id = $createdById;
            $queuePermanentStorage->save();
            $this->createWarehouseLog($entityDetails['entity_model'], $entityDetails['entity_id'], QueuedSubLocationModel::class, $queuePermanentStorage->id, $queuePermanentStorage->getAttributes(), $createdById, 0);
            $this->onCreateStockLogs($itemId, $action, count($currentScannedItems), $subLocationId, $layerLevel, $currentLayerCapacity, $createdById, $referenceNumber, $stockLogTransactionNumber);
            return $queuePermanentStorage->getAttributes();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onCreateStockLogs($itemId, $action, $quantity, $subLocationId, $layerLevel, $storageRemainingSpace, $createdById, $referenceNumber, $stockLogTransactionNumber)
    {
        try {
            $stockInventory = StockInventoryModel::where('item_id', $itemId)->first();
            $currentStock = 0;
            if ($stockInventory) {
                $currentStock = $stockInventory->stock_count;
            }
            $totalCurrentStock = $action == 1 ? $currentStock + $quantity : $currentStock - $quantity;
            $existingStockLogs = StockLogModel::where('transaction_number', $stockLogTransactionNumber)->first();
            if ($existingStockLogs) {
                $existingStockLogs->storage_remaining_space = $storageRemainingSpace;
                $existingStockLogs->quantity += $quantity;
                $existingStockLogs->final_stock = $totalCurrentStock;
                $existingStockLogs->save();
            } else {
                $stockLogs = new StockLogModel();
                $stockLogs->item_id = $itemId;
                $stockLogs->action = $action;
                $stockLogs->quantity = $quantity;
                $stockLogs->initial_stock = $currentStock;
                $stockLogs->final_stock = $totalCurrentStock;
                $stockLogs->sub_location_id = $subLocationId;
                $stockLogs->layer_level = $layerLevel;
                $stockLogs->reference_number = $referenceNumber;
                $stockLogs->storage_remaining_space = $storageRemainingSpace;
                $stockLogs->created_by_id = $createdById;
                $stockLogs->transaction_number = $stockLogTransactionNumber;
                $stockLogs->save();
            }

            $this->onCreateUpdateStockInventories($itemId, $action, $quantity, $createdById);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onCreateUpdateStockInventories($itemId, $action, $quantity, $createdById)
    {
        try {
            $stockInventoryModel = StockInventoryModel::where('item_id', $itemId)->first();
            if ($stockInventoryModel) {
                if ($action == 1) {
                    $stockInventoryModel->stock_count += $quantity;
                } else {
                    $stockInventoryModel->stock_count -= $quantity;
                }
                $stockInventoryModel->updated_by_id = $createdById;
            } else {
                $stockInventoryModel = new StockInventoryModel();
                $stockInventoryModel->item_id = $itemId;
                $stockInventoryModel->stock_count = $quantity;
            }
            $stockInventoryModel->created_by_id = $createdById;
            $stockInventoryModel->save();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onQueueTemporaryStorage($createdById, $scannedItem, $subLocationId, $referenceNumber)
    {
        try {
            $subLocation = SubLocationModel::where('id', $subLocationId)
                ->where('is_permanent', 0)
                ->firstOrFail();
            $layers = json_decode($subLocation->layers, true);
            $currentLayerIndex = 1;
            $currentLayerCapacity = $layers[$currentLayerIndex]['max'];
            $currentScannedItems = [];
            $scanCtr = 1;

            $queueTemporaryStorageArr = [];
            $isSpareLayer = false;
            $spareScannedItems = [];
            $itemsToBeAdjusted = [];
            foreach ($scannedItem as $value) {
                $productionBatchModel = ProductionBatchModel::find($value['bid']);
                $producedItems = json_decode($productionBatchModel->productionItems->produced_items, true);
                if ($isSpareLayer) {
                    $spareScannedItems[] = $value;
                    $this->onUpdateItemLocationLog($value['bid'], $value['sticker_no'], $subLocationId, 0, $createdById, false, $referenceNumber);
                } else {
                    $currentScannedItems[] = $value;
                    --$currentLayerCapacity;
                    $this->onUpdateItemLocationLog($value['bid'], $value['sticker_no'], $subLocationId, $currentLayerIndex, $createdById, false, $referenceNumber);

                    if ($currentLayerCapacity === 0 || (count($scannedItem) === $scanCtr)) {
                        $queueTemporaryStorage = new QueuedTemporaryStorageModel();
                        $queueTemporaryStorage->sub_location_id = $subLocationId;
                        $queueTemporaryStorage->layer_level = $currentLayerIndex;
                        $queueTemporaryStorage->production_items = json_encode($currentScannedItems);
                        $queueTemporaryStorage->quantity = count($currentScannedItems);
                        $queueTemporaryStorage->storage_remaining_space = $currentLayerCapacity;
                        $queueTemporaryStorage->created_by_id = $createdById;
                        $queueTemporaryStorage->save();
                        $currentLayerCapacity = $layers[$currentLayerIndex]['max'];
                        $currentLayerIndex++;
                        $currentScannedItems = [];
                        $queueTemporaryStorageArr[] = $queueTemporaryStorage;
                        if (!isset($layers[$currentLayerIndex])) {
                            $isSpareLayer = true;

                        }
                    }
                    $scanCtr++;

                    if ($producedItems[$value['sticker_no']]['status'] == 14) {
                        $storedSubLocationId = $producedItems[$value['sticker_no']]['sub_location']['sub_location_id'];
                        $storedLayerIndex = $producedItems[$value['sticker_no']]['sub_location']['layer_level'];
                        $itemId = $productionBatchModel->productionOta->itemMasterdata->id ?? $productionBatchModel->productionOtb->itemMasterdata->id;

                        $storedItemArrayKey = "{$storedSubLocationId}-{$storedLayerIndex}-{$itemId}";
                        if (!isset($itemsToBeAdjusted[$storedItemArrayKey])) {
                            $itemsToBeAdjusted[$storedItemArrayKey] = [
                                'stored_sub_location_id' => $storedSubLocationId,
                                'stored_layer_level' => $storedLayerIndex,
                                'item_id' => $itemId,
                                'produced_items' => []
                            ];
                        }

                        $itemsToBeAdjusted[$storedItemArrayKey]['produced_items'][] = [
                            'bid' => $value['bid'],
                            'sticker_no' => $value['sticker_no'],
                        ];
                    }
                }
            }
            if (count($spareScannedItems) > 0) {
                $queueTemporaryStorage = new QueuedTemporaryStorageModel();
                $queueTemporaryStorage->sub_location_id = $subLocationId;
                $queueTemporaryStorage->layer_level = 0;
                $queueTemporaryStorage->production_items = json_encode($spareScannedItems);
                $queueTemporaryStorage->quantity = count($spareScannedItems);
                $queueTemporaryStorage->storage_remaining_space = 0;
                $queueTemporaryStorage->created_by_id = $createdById;
                $queueTemporaryStorage->save();
                $queueTemporaryStorageArr[] = $queueTemporaryStorage;
            }

            if (count($itemsToBeAdjusted) > 0) {
                $stockLogTransactionNumber = StockLogModel::onGetCurrentTransactionNumber() + 1;
                $this->onDecrementStorageAndStock($itemsToBeAdjusted, $createdById, $referenceNumber, $stockLogTransactionNumber);
            }

            return $queueTemporaryStorageArr;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onUpdateItemLocationLog($productionBatchId, $stickerNumber, $subLocationId, $currentLayerIndex, $createdById, $isPermanent = false, $referenceNumber = null)
    {
        try {
            $productionBatch = ProductionBatchModel::find($productionBatchId);
            $productionItem = $productionBatch->productionItems;
            $items = json_decode($productionBatch->productionItems->produced_items, true);

            $subLocation = [
                'sub_location_id' => $subLocationId,
                'layer_level' => $currentLayerIndex
            ];
            $items[$stickerNumber]['sub_location'] = $subLocation;
            if ($isPermanent) {
                $items[$stickerNumber]['stored_sub_location'] = $subLocation;
                $items[$stickerNumber]['status'] = 13; // stored
            }
            $productionItem->produced_items = json_encode($items);
            $productionItem->save();
            $this->createProductionLog(ProductionItemModel::class, $productionItem->id, $items[$stickerNumber], $createdById, 1, $stickerNumber);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onDecrementStorageAndStock($itemsToBeAdjusted, $createdById, $referenceNumber, $stockLogTransactionNumber)
    {
        try {

            foreach ($itemsToBeAdjusted as $itemDetails) {

                $queuedSubLocation = QueuedSubLocationModel::where([
                    'sub_location_id' => $itemDetails['stored_sub_location_id'],
                    'layer_level' => $itemDetails['stored_layer_level'],
                ])
                    ->orderBy('id', 'DESC')
                    ->first();

                $releaseStorageSpace = 0;
                // UNSETTING OF THE REMOVED ITEM FROM PERMANENT STORAGE
                if ($queuedSubLocation) {
                    $storedItems = json_decode($queuedSubLocation->production_items, true);
                    foreach ($storedItems as $key => $storedItem) {
                        foreach ($itemDetails['produced_items'] as $scannedItems) {
                            if ($storedItem['bid'] == $scannedItems['bid'] && $storedItem['sticker_no'] == $scannedItems['sticker_no']) {
                                $releaseStorageSpace++;
                                unset($storedItems[$key]);
                                break;
                            }
                        }
                    }

                    $storageRemainingSpace = $queuedSubLocation->storage_remaining_space + $releaseStorageSpace;
                    $newQueuedSubLocation = new QueuedSubLocationModel();
                    $newQueuedSubLocation->sub_location_id = $queuedSubLocation->sub_location_id;
                    $newQueuedSubLocation->layer_level = $queuedSubLocation->layer_level;
                    $newQueuedSubLocation->quantity = count($storedItems);
                    $newQueuedSubLocation->production_items = json_encode(array_values($storedItems));
                    $newQueuedSubLocation->storage_remaining_space = $storageRemainingSpace;
                    $newQueuedSubLocation->created_by_id = $createdById;
                    $newQueuedSubLocation->save();
                    $this->createWarehouseLog(null, null, QueuedSubLocationModel::class, $newQueuedSubLocation->id, $newQueuedSubLocation->getAttributes(), $createdById, 0);

                }
                // DECREMENT STOCK INVENTORY AND CREATE STOCK LOG
                $this->onCreateStockLogs($itemDetails['item_id'], 0, count($itemDetails['produced_items']), $itemDetails['stored_sub_location_id'], $itemDetails['stored_layer_level'], $storageRemainingSpace, $createdById, $referenceNumber, $stockLogTransactionNumber);
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onGetQueuedItems($subLocationId, $isPermanent)
    {
        $data = null;
        if ($isPermanent) {
            $data = QueuedSubLocationModel::where('sub_location_id', $subLocationId)->get()->pluck('production_items');
        } else {
            $data = QueuedTemporaryStorageModel::where('sub_location_id', $subLocationId)->get()->pluck('production_items');
        }
        $decodedItems = $this->onDecodeScannedItems($data);
        if (count($decodedItems) > 0) {
            return $decodedItems;
        }
        throw new Exception('Record not found');
    }

    public function onDecodeScannedItems($queueProdItems)
    {
        $decodedArr = [];
        foreach ($queueProdItems as $value) {
            $decodedArr[] = json_decode($value, true);
        }
        return $decodedArr;
    }

    public function onCheckAvailability($subLocationId, $isPermanent, $layerLevel = null, $createdById = null)
    {
        try {
            if ($isPermanent) {
                $subLocation = SubLocationModel::where('id', $subLocationId)->where('is_permanent', 1)->first();

                if (!$subLocation) {
                    return false;
                }
                return QueuedSubLocationModel::where('sub_location_id', $subLocationId)
                    ->where('layer_level', $layerLevel)
                    ->where('status', 1) // Add this condition to check if status is active
                    ->where('created_by_id', '!=', $createdById)
                    ->exists();
            } else {
                $subLocation = SubLocationModel::where('id', $subLocationId)->where('is_permanent', 0)->first();

                if (!$subLocation) {
                    return false;
                }

                return !QueuedTemporaryStorageModel::where('sub_location_id', $subLocation->id)->exists();
            }
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public function onCheckStorageSpace($subLocationId, $layer, $isPermanent)
    {
        try {
            $subLocationStorageSpace = null;

            $subLocation = SubLocationModel::where('id', $subLocationId)
                ->where('is_permanent', $isPermanent ? 1 : 0)
                ->first();

            if (!$subLocation) {
                return false;
            }
            $size = json_decode($subLocation->layers, true)[$layer]['max'];
            $remainingCapacity = $size;

            $queuedModel = $isPermanent ? QueuedSubLocationModel::class : QueuedTemporaryStorageModel::class;
            $subLocationStorageSpace = $queuedModel::where([
                'sub_location_id' => $subLocationId,
                'layer_level' => $layer
            ])->orderBy('id', 'DESC')->first();

            if ($subLocationStorageSpace) {
                $remainingCapacity = $subLocationStorageSpace->storage_remaining_space;
            }
            $data = [
                'is_full' => $remainingCapacity > 0,
                'current_size' => $size,
                'allocated_space' => $size - $remainingCapacity,
                'remaining_space' => $remainingCapacity
            ];
            return $data;
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public function onGetSubLocationDetails($subLocationId, $layer, $isPermanent)
    {
        try {
            $subLocation = SubLocationModel::where('id', $subLocationId);
            $currentSize = 0;
            $remainingCapacity = 0;
            if ($isPermanent) {
                $subLocation = $subLocation->where('is_permanent', 1)->firstOrFail();
                $subLocationDefaultCapacity = json_decode($subLocation->layers, true)[$layer]['max'];
                $currentSize = $subLocationDefaultCapacity;

                $queuedSubLocation = QueuedSubLocationModel::where([
                    'sub_location_id' => $subLocationId,
                    'layer_level' => $layer
                ])->orderBy('id', 'DESC')->first();
                if ($queuedSubLocation) {
                    $subLocationDefaultCapacity = $queuedSubLocation->storage_remaining_space;
                }
                $remainingCapacity = $subLocationDefaultCapacity;
            } else {
                $subLocation = $subLocation->where('is_permanent', 0)->firstOrFail();
                $subLocationDefaultCapacity = json_decode($subLocation->layers, true)[$layer]['max'];

                $queuedTemporaryLocation = QueuedTemporaryStorageModel::where('sub_location_id', $subLocationId)->first();
                if ($queuedTemporaryLocation) {
                    $subLocationDefaultCapacity = $queuedTemporaryLocation->storage_remaining_space;
                }
            }

            $data = [
                'sub_location_details' => $subLocation,
                'current_size' => $currentSize,
                'allocated_space' => $currentSize - $remainingCapacity,
                'remaining_capacity' => $remainingCapacity,
                'layer' => $layer
            ];

            return $data;

        } catch (Exception $exception) {
            throw $exception;
        }
    }

    // Create a function in creating single-item storage for decrementing and incrementing stocks
    public function onAdjustSingleItemStock($scannedItems, $adjustmentType, $referenceNumber, $createdById)
    {
        // Adjustment Type = 1 For Increment 0 = Decrement
        try {
            switch ($adjustmentType) {
                case 0:
                    $this->onDecrementStock($scannedItems, $referenceNumber, $createdById);
                    break;
                case 1:
                    $this->onIncrementStock($scannedItems, $referenceNumber, $createdById);
                    break;
                default:
                    throw new Exception('Invalid adjustment type');
            }

        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public function onDecrementStock($scannedItems, $referenceNumber, $createdById)
    {
        $subLocationToAdjustArray = [];
        #region Validation and Retrieval of Items to Remove
        foreach ($scannedItems as $item) {
            $productionBatch = ProductionBatchModel::find($item['bid']);
            $itemId = $productionBatch->itemMasterdata->id;
            $productionItems = $productionBatch->productionItems;
            $itemToRemoveDetails = json_decode($productionItems->produced_items, true)[$item['sticker_no']];
            $subLocationId = $itemToRemoveDetails['stored_sub_location']['sub_location_id'];
            $layerLevel = $itemToRemoveDetails['stored_sub_location']['layer_level'];
            $itemToUnset = $item['bid'] . '-' . $item['sticker_no'];

            // Queued Sub Location Update Unset code be bussin
            $queuedSubLocationModel = QueuedSubLocationModel::select(['production_items', 'storage_remaining_space', 'quantity'])->
                where([
                    'sub_location_id' => $subLocationId,
                    'layer_level' => $layerLevel
                ])->orderBy('id', 'DESC')->first();

            if ($queuedSubLocationModel) {
                $preMappedQueuedItems = [];
                foreach (json_decode($queuedSubLocationModel->production_items, true) as $queuedItems) {
                    $preMappedQueuedItems[$queuedItems['bid'] . '-' . $queuedItems['sticker_no']] = $queuedItems;
                }
                if (array_key_exists($itemToUnset, $preMappedQueuedItems)) {

                    if (!isset($subLocationToAdjustArray["$subLocationId-$layerLevel"])) {
                        $subLocationToAdjustArray["$subLocationId-$layerLevel"] = [];
                    }
                    if (!isset($subLocationToAdjustArray["$subLocationId-$layerLevel"]['pre_mapped_items'])) {
                        $subLocationToAdjustArray["$subLocationId-$layerLevel"]['pre_mapped_items'] = $preMappedQueuedItems;
                    }
                    if (!isset($subLocationToAdjustArray["$subLocationId-$layerLevel"]['items'][$itemId])) {
                        $subLocationToAdjustArray["$subLocationId-$layerLevel"]['items'][$itemId] = [];
                    }
                    $subLocationToAdjustArray["$subLocationId-$layerLevel"]['items'][$itemId][] = [
                        'bid' => $item['bid'],
                        'sticker_no' => $item['sticker_no'],
                    ];
                }
            }
        }
        #endregion

        #region Item Removal Methods
        foreach ($subLocationToAdjustArray as $subLocationIdLayerLevel => $itemIdArray) {
            $subLocationId = explode('-', $subLocationIdLayerLevel)[0];
            $layerLevel = explode('-', $subLocationIdLayerLevel)[1];
            $queuedSubLocationModel = QueuedSubLocationModel::where([
                'sub_location_id' => $subLocationId,
                'layer_level' => $layerLevel
            ])->orderBy('id', 'DESC')->first();
            $preMappedItems = $itemIdArray['pre_mapped_items'];
            $itemCount = 0;
            foreach ($itemIdArray['items'] as $itemId => $itemValue) {
                foreach ($itemValue as $item) {
                    $itemKey = $item['bid'] . '-' . $item['sticker_no'];
                    if (array_key_exists($itemKey, $preMappedItems)) {
                        unset($preMappedItems[$itemKey]);
                        $itemCount++;
                    }
                }

                // Stock Log Update
                $stockLogModel = StockLogModel::where([
                    'sub_location_id' => $subLocationId,
                    'layer_level' => $layerLevel
                ])->orderBy('id', 'DESC')
                    ->first();
                $stockItemLogModel = StockLogModel::where([
                    'sub_location_id' => $subLocationId,
                    'layer_level' => $layerLevel,
                    'item_id' => $itemId
                ])->orderBy('id', 'DESC')
                    ->first();
                $storageRemainingSpace = 0;
                if ($stockLogModel) {
                    $storageRemainingSpace = $stockLogModel->storage_remaining_space;
                } else {
                    $subLocationModel = SubLocationModel::find($subLocationId);
                    $layers = json_decode($subLocationModel->layers, true);
                    $storageRemainingSpace = $layers[$layerLevel]['max'];
                }
                $newStockLogModel = new stockLogModel();
                $newStockLogModel->reference_number = $referenceNumber;
                $newStockLogModel->action = 0;
                $newStockLogModel->item_id = $itemId;
                $newStockLogModel->quantity = count($itemValue);
                $newStockLogModel->sub_location_id = $subLocationId;
                $newStockLogModel->layer_level = $layerLevel;
                $newStockLogModel->storage_remaining_space = $storageRemainingSpace + count($itemValue);
                $newStockLogModel->initial_stock = $stockItemLogModel->final_stock;
                $newStockLogModel->final_stock = $stockItemLogModel->final_stock - count($itemValue);
                $newStockLogModel->created_by_id = $createdById;
                $newStockLogModel->save();

                // Stock Inventory Update
                $stockInventoryModel = StockInventoryModel::where('item_id', $itemId)->first();
                $stockInventoryModel->stock_count -= count($itemValue);
                $stockInventoryModel->save();
            }

            // Queued Sub Location Update
            $newQueuedSubLocationModel = new QueuedSubLocationModel();
            $newQueuedSubLocationModel->sub_location_id = $subLocationId;
            $newQueuedSubLocationModel->layer_level = $layerLevel;
            $newQueuedSubLocationModel->production_items = json_encode(array_values($preMappedItems));
            $newQueuedSubLocationModel->storage_remaining_space = $queuedSubLocationModel->storage_remaining_space + $itemCount;
            $newQueuedSubLocationModel->quantity = $queuedSubLocationModel->quantity - $itemCount;
            $newQueuedSubLocationModel->created_by_id = $createdById;
            $newQueuedSubLocationModel->save();

        }
        #endregion
    }

    public function onIncrementStock($scannedItems, $referenceNumber, $createdById)
    {
        $subLocationToAdjustArray = [];
        #region Validation and Retrieval of Items to Put Back
        foreach ($scannedItems as $item) {
            $productionBatch = ProductionBatchModel::find($item['bid']);
            $itemMasterdata = $productionBatch->itemMasterdata;
            $itemId = $itemMasterdata->id;
            $itemCode = $itemMasterdata->item_code;
            $productionItemModel = ProductionItemModel::where('production_batch_id', $item['bid'])->first();
            $producedItems = json_decode($productionItemModel->produced_items, true)[$item['sticker_no']];
            $lastStoredSubLocationId = $producedItems['stored_sub_location']['sub_location_id'];
            $lastStoredLayerLevel = $producedItems['stored_sub_location']['layer_level'];

            // Queued Sub Location Update Unset code be bussin
            $itemToUnset = $item['bid'] . '-' . $item['sticker_no'];

            // Queued Sub Location Update Unset code be bussin
            $queuedSubLocationModel = QueuedSubLocationModel::select(['production_items', 'storage_remaining_space', 'quantity'])->
                where([
                    'sub_location_id' => $lastStoredSubLocationId,
                    'layer_level' => $lastStoredLayerLevel
                ])->orderBy('id', 'DESC')->first();

            if ($queuedSubLocationModel) {
                $preMappedQueuedItems = [];
                foreach (json_decode($queuedSubLocationModel->production_items, true) as $queuedItems) {
                    $preMappedQueuedItems[$queuedItems['bid'] . '-' . $queuedItems['sticker_no']] = $queuedItems;
                }
                if (!array_key_exists($itemToUnset, $preMappedQueuedItems)) {
                    if (!isset($subLocationToAdjustArray["$lastStoredSubLocationId-$lastStoredLayerLevel"])) {
                        $subLocationToAdjustArray["$lastStoredSubLocationId-$lastStoredLayerLevel"] = [];
                    }
                    if (!isset($subLocationToAdjustArray["$lastStoredSubLocationId-$lastStoredLayerLevel"]['pre_mapped_items'])) {
                        $subLocationToAdjustArray["$lastStoredSubLocationId-$lastStoredLayerLevel"]['pre_mapped_items'] = $preMappedQueuedItems;
                    }
                    if (!isset($subLocationToAdjustArray["$lastStoredSubLocationId-$lastStoredLayerLevel"]['items'][$itemId])) {
                        $subLocationToAdjustArray["$lastStoredSubLocationId-$lastStoredLayerLevel"]['items'][$itemId] = [];
                    }
                    $subLocationToAdjustArray["$lastStoredSubLocationId-$lastStoredLayerLevel"]['items'][$itemId][] = [
                        'bid' => $item['bid'],
                        "item_code" => $itemCode,
                        "item_id" => $itemId,
                        'sticker_no' => $item['sticker_no'],
                        "q" => $producedItems['q'],
                        "batch_code" => $producedItems['batch_code'],
                        "parent_batch_code" => $producedItems['parent_batch_code'],

                    ];
                }
            }
        }
        #endregion

        #region Item Re-Stock Methods
        foreach ($subLocationToAdjustArray as $subLocationIdLayerLevel => $itemIdArray) {
            $subLocationId = explode('-', $subLocationIdLayerLevel)[0];
            $layerLevel = explode('-', $subLocationIdLayerLevel)[1];
            $queuedSubLocationModel = QueuedSubLocationModel::where([
                'sub_location_id' => $subLocationId,
                'layer_level' => $layerLevel
            ])->orderBy('id', 'DESC')->first();
            $preMappedItems = $itemIdArray['pre_mapped_items'];
            $itemCount = 0;

            foreach ($itemIdArray['items'] as $itemId => $itemValue) {
                foreach ($itemValue as $item) {
                    $itemKey = $item['bid'] . '-' . $item['sticker_no'];
                    if (!array_key_exists($itemKey, $preMappedItems)) {
                        $itemCount++;
                        $preMappedItems[$itemKey] = $item;
                    }
                }

                // Stock Log Update
                $stockLogModel = StockLogModel::where([
                    'sub_location_id' => $subLocationId,
                    'layer_level' => $layerLevel
                ])->orderBy('id', 'DESC')
                    ->first();
                $stockItemLogModel = StockLogModel::where([
                    'sub_location_id' => $subLocationId,
                    'layer_level' => $layerLevel,
                    'item_id' => $itemId
                ])->orderBy('id', 'DESC')
                    ->first();
                $storageRemainingSpace = 0;
                if ($stockLogModel) {
                    $storageRemainingSpace = $stockLogModel->storage_remaining_space;
                } else {
                    $subLocationModel = SubLocationModel::find($subLocationId);
                    $layers = json_decode($subLocationModel->layers, true);
                    $storageRemainingSpace = $layers[$layerLevel]['max'];
                }
                $newStockLogModel = new stockLogModel();
                $newStockLogModel->reference_number = $referenceNumber;
                $newStockLogModel->action = 0;
                $newStockLogModel->item_id = $itemId;
                $newStockLogModel->quantity = count($itemValue);
                $newStockLogModel->sub_location_id = $subLocationId;
                $newStockLogModel->layer_level = $layerLevel;
                $newStockLogModel->storage_remaining_space = $storageRemainingSpace - count($itemValue);
                $newStockLogModel->initial_stock = $stockItemLogModel->final_stock;
                $newStockLogModel->final_stock = $stockItemLogModel->final_stock + count($itemValue);
                $newStockLogModel->created_by_id = $createdById;
                $newStockLogModel->save();

                // Stock Inventory Update
                $stockInventoryModel = StockInventoryModel::where('item_id', $itemId)->first();
                $stockInventoryModel->stock_count += count($itemValue);
                $stockInventoryModel->save();
            }

            // Queued Sub Location Update
            $newQueuedSubLocationModel = new QueuedSubLocationModel();
            $newQueuedSubLocationModel->sub_location_id = $subLocationId;
            $newQueuedSubLocationModel->layer_level = $layerLevel;
            $newQueuedSubLocationModel->production_items = json_encode(array_values($preMappedItems));
            $newQueuedSubLocationModel->storage_remaining_space = $queuedSubLocationModel->storage_remaining_space - $itemCount;
            $newQueuedSubLocationModel->quantity = $queuedSubLocationModel->quantity + $itemCount;
            $newQueuedSubLocationModel->created_by_id = $createdById;
            $newQueuedSubLocationModel->save();

        }
        #endregion
    }

}

/*
 unset($preMappedQueuedItems[$itemToUnset]);
                        $logQueuedSubLocationModel = new QueuedSubLocationModel();
                        $logQueuedSubLocationModel->sub_location_id = $subLocationId;
                        $logQueuedSubLocationModel->layer_level = $layerLevel;
                        $logQueuedSubLocationModel->production_items = json_encode(array_values($preMappedQueuedItems));
                        $logQueuedSubLocationModel->storage_remaining_space -= 1;
                        $logQueuedSubLocationModel->quantity = count($preMappedQueuedItems);
                        $logQueuedSubLocationModel->created_by_id = $createdById;
                        $logQueuedSubLocationModel->save();
 */
