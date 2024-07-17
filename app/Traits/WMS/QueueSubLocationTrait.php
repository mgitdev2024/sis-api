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

    public function onQueueStorage($createdById, $scannedItems, $subLocationId, $isPermanent, $layerLevel = null, $entityModel = null, $entityId = null)
    {
        try {
            $entityDetails = [
                'entity_model' => $entityModel,
                'entity_id' => $entityId,
            ];
            $data = null;
            DB::beginTransaction();
            if ($isPermanent) {
                $data = $this->onQueuePermanentStorage($createdById, $scannedItems, $subLocationId, $layerLevel, $entityDetails);
            } else {
                $data = $this->onQueueTemporaryStorage($createdById, $scannedItems, $subLocationId);
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
    public function onQueuePermanentStorage($createdById, $scannedItems, $subLocationId, $layerLevel, $entityDetails)
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
            if ($queuedPermanentStorageModel) {
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
            $queuePermanentStorage = new QueuedSubLocationModel();
            $queuePermanentStorage->sub_location_id = $subLocationId;
            $queuePermanentStorage->layer_level = $layerLevel;
            $queuePermanentStorage->production_items = json_encode($currentScannedItems);
            $queuePermanentStorage->quantity = count($currentScannedItems);
            $queuePermanentStorage->storage_remaining_space = $currentLayerCapacity;
            $queuePermanentStorage->created_by_id = $createdById;
            $queuePermanentStorage->save();
            $this->createWarehouseLog($entityDetails['entity_model'], $entityDetails['entity_id'], QueuedSubLocationModel::class, $queuePermanentStorage->id, $queuePermanentStorage->getAttributes(), $createdById, 0);

            $this->onCreateUpdateStockInventories($itemCode, 1, count($currentScannedItems), $subLocationId, $layerLevel, $currentLayerCapacity, $createdById);
            return $queuePermanentStorage->getAttributes();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }


    public function onCreateStockLogs($itemCode, $action, $quantity, $subLocationId, $layerLevel, $storageRemainingSpace, $createdById)
    {
        try {
            $stockLogs = new StockLogModel();
            $stockLogs->item_code = $itemCode;
            $stockLogs->action = $action;
            $stockLogs->quantity = $quantity;
            $stockLogs->sub_location_id = $subLocationId;
            $stockLogs->layer_level = $layerLevel;
            $stockLogs->storage_remaining_space = $storageRemainingSpace;
            $stockLogs->created_by_id = $createdById;
            $stockLogs->save();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onCreateUpdateStockInventories($itemCode, $action, $quantity, $subLocationId, $layerLevel, $storageRemainingSpace, $createdById)
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
            $this->onCreateStockLogs($itemCode, $action, $quantity, $subLocationId, $layerLevel, $storageRemainingSpace, $createdById);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
    public function onQueueTemporaryStorage($createdById, $scannedItem, $subLocationId)
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
            foreach ($scannedItem as $value) {
                if ($isSpareLayer) {
                    $spareScannedItems[] = $value;
                    $this->onUpdateItemLocationLog($value['bid'], $value['sticker_no'], $subLocationId, 0, $createdById);
                } else {
                    $currentScannedItems[] = $value;
                    --$currentLayerCapacity;
                    $this->onUpdateItemLocationLog($value['bid'], $value['sticker_no'], $subLocationId, $currentLayerIndex, $createdById);

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
            return $queueTemporaryStorageArr;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onUpdateItemLocationLog($productionBatchId, $stickerNumber, $subLocationId, $currentLayerIndex, $createdById, $isPermanent = false)
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
            $subLocationStorageSpace = $queuedModel::where('sub_location_id', $subLocationId)->first();

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
                    'sub_location_id'=> $subLocationId,
                    'layer_level'=> $layer
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
