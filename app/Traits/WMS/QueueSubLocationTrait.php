<?php

namespace App\Traits\WMS;

use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Storage\QueuedSubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use Exception;
use App\Traits\ResponseTrait;
use App\Traits\WMS\WarehouseLogTrait;
use App\Traits\MOS\ProductionLogTrait;

use DB;

trait QueueSubLocationTrait
{
    use ResponseTrait, WarehouseLogTrait, ProductionLogTrait;

    public function onQueueStorage($createdById, $scannedItems, $subLocationId, $isPermanent, $entityModel = null, $entityId = null)
    {
        try {
            $entityDetails = [
                'entity_model' => $entityModel,
                'entity_id' => $entityId,
            ];
            $data = null;
            DB::beginTransaction();
            if ($isPermanent) {
                $data = $this->onQueuePermanentStorage($createdById, $scannedItems, $subLocationId, $entityDetails);
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
    public function onQueuePermanentStorage($createdById, $scannedItems, $subLocationId, $entityDetails)
    {
        return null;
    }

    public function onQueueTemporaryStorage($createdById, $scannedItem, $subLocationId)
    {
        try {
            $subLocation = SubLocationModel::find($subLocationId);
            $layers = json_decode($subLocation->layers, true);
            $currentLayerIndex = 1;
            $currentLayerCapacity = $layers[$currentLayerIndex]['max'];
            $currentScannedItems = [];
            $scanCtr = 1;

            $queueTemporaryStorageArr = [];

            foreach ($scannedItem as $value) {
                $currentScannedItems[] = $value;
                --$currentLayerCapacity;
                $this->onUpdateItemLocationLog($value['bid'], $value['sticker_no'], $subLocationId, $currentLayerIndex, $createdById);
                if ($currentLayerCapacity == 0 || (count($scannedItem) == $scanCtr)) {
                    $queueTemporaryStorage = new QueuedTemporaryStorageModel();
                    $queueTemporaryStorage->sub_location_id = $subLocationId;
                    $queueTemporaryStorage->layer_level = $currentLayerIndex;
                    $queueTemporaryStorage->production_items = json_encode($currentScannedItems);
                    $queueTemporaryStorage->quantity = count($currentScannedItems);
                    $queueTemporaryStorage->storage_remaining_space = $currentLayerCapacity;
                    $queueTemporaryStorage->created_by_id = $createdById;
                    $queueTemporaryStorage->save();
                    $currentLayerIndex++;
                    $currentScannedItems = [];
                    $queueTemporaryStorageArr[] = $queueTemporaryStorage;
                    if (isset($layers[$currentLayerIndex])) {
                        break;
                    }
                }
                $scanCtr++;
            }

            return $queueTemporaryStorage;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onUpdateItemLocationLog($productionBatchId, $stickerNumber, $subLocationId, $currentLayerIndex, $createdById)
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
            $productionItem->produced_items = json_encode($items);
            $productionItem->save();

            $this->createProductionLog(ProductionItemModel::class, $productionItem->id, $items[$stickerNumber], $createdById, 1, $stickerNumber);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onGetScannedItems($subLocationId, $isPermanent)
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
        return $queueProdItems;
    }
}
