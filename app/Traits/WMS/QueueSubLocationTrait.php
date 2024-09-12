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

    public function onQueueStorage($createdById, $scannedItems, $subLocationId, $isPermanent, $layerLevel = null, $entityModel = null, $entityId = null, $referenceNumber = null, $action = 1)
    {
        try {
            $entityDetails = [
                'entity_model' => $entityModel,
                'entity_id' => $entityId,
            ];
            $data = null;
            DB::beginTransaction();
            if ($isPermanent) {
                $data = $this->onQueuePermanentStorage($createdById, $scannedItems, $subLocationId, $layerLevel, $entityDetails, $referenceNumber, $action);
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
    public function onQueuePermanentStorage($createdById, $scannedItems, $subLocationId, $layerLevel, $entityDetails, $referenceNumber, $action)
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

            $itemCode = null;
            $currentScannedItems = [];
            foreach ($scannedItems as $value) {
                if ($currentLayerCapacity > 0) {
                    $itemCode = $value['item_code'];
                    $currentScannedItems[] = $value;
                    --$currentLayerCapacity;

                    $this->onUpdateItemLocationLog($value['bid'], $value['sticker_no'], $subLocationId, $layerLevel, $createdById, true);
                }
            }
            $existingItemStored = array_merge($existingItemStored, $currentScannedItems);
            $queuePermanentStorage = new QueuedSubLocationModel();
            $queuePermanentStorage->sub_location_id = $subLocationId;
            $queuePermanentStorage->layer_level = $layerLevel;
            $queuePermanentStorage->production_items = json_encode($existingItemStored);
            $queuePermanentStorage->quantity = count($currentScannedItems) + count($existingItemStored);
            $queuePermanentStorage->storage_remaining_space = $currentLayerCapacity;
            $queuePermanentStorage->created_by_id = $createdById;
            $queuePermanentStorage->save();
            $this->createWarehouseLog($entityDetails['entity_model'], $entityDetails['entity_id'], QueuedSubLocationModel::class, $queuePermanentStorage->id, $queuePermanentStorage->getAttributes(), $createdById, 0);
            $this->onCreateStockLogs($itemCode, 1, count($currentScannedItems), $subLocationId, $layerLevel, $currentLayerCapacity, $createdById, $referenceNumber);
            return $queuePermanentStorage->getAttributes();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onCreateStockLogs($itemCode, $action, $quantity, $subLocationId, $layerLevel, $storageRemainingSpace, $createdById, $referenceNumber)
    {
        try {
            $stockInventory = StockInventoryModel::where('item_code', $itemCode)->first();
            $currentStock = 0;
            if ($stockInventory) {
                $currentStock = $stockInventory->stock_count;
            }
            $totalCurrentStock = $action == 1 ? $currentStock + $quantity : $currentStock - $quantity;
            $stockLogs = new StockLogModel();
            $stockLogs->item_code = $itemCode;
            $stockLogs->action = $action;
            $stockLogs->quantity = $quantity;
            $stockLogs->initial_stock = $currentStock;
            $stockLogs->final_stock = $totalCurrentStock;
            $stockLogs->sub_location_id = $subLocationId;
            $stockLogs->layer_level = $layerLevel;
            $stockLogs->reference_number = $referenceNumber;
            $stockLogs->storage_remaining_space = $storageRemainingSpace;
            $stockLogs->created_by_id = $createdById;
            $stockLogs->save();
            $this->onCreateUpdateStockInventories($itemCode, $action, $quantity, $createdById);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onCreateUpdateStockInventories($itemCode, $action, $quantity, $createdById)
    {
        try {
            $stockInventoryModel = StockInventoryModel::where('item_code', $itemCode)->first();
            if ($stockInventoryModel) {
                if ($action == 1) {
                    $stockInventoryModel->stock_count += $quantity;
                } else {
                    $stockInventoryModel->stock_count -= $quantity;
                }
                $stockInventoryModel->updated_by_id = $createdById;
            } else {
                $stockInventoryModel = new StockInventoryModel();
                $stockInventoryModel->item_code = $itemCode;
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
                        $itemCode = $productionBatchModel->item_code;

                        $storedItemArrayKey = "{$storedSubLocationId}-{$storedLayerIndex}-{$itemCode}";
                        if (!isset($itemsToBeAdjusted[$storedItemArrayKey])) {
                            $itemsToBeAdjusted[$storedItemArrayKey] = [
                                'stored_sub_location_id' => $storedSubLocationId,
                                'stored_layer_level' => $storedLayerIndex,
                                'item_code' => $itemCode,
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
                $this->onDecrementStorageAndStock($itemsToBeAdjusted, $createdById, $referenceNumber);
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
                $items[$stickerNumber]['status'] = 13; // stored
            }
            $productionItem->produced_items = json_encode($items);
            $productionItem->save();
            $this->createProductionLog(ProductionItemModel::class, $productionItem->id, $items[$stickerNumber], $createdById, 1, $stickerNumber);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onDecrementStorageAndStock($itemsToBeAdjusted, $createdById, $referenceNumber)
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
                $this->onCreateStockLogs($itemDetails['item_code'], 0, count($itemDetails['produced_items']), $itemDetails['stored_sub_location_id'], $itemDetails['stored_layer_level'], $storageRemainingSpace, $createdById, $referenceNumber);
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
            ])->first();

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
                ])->first();
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
}
