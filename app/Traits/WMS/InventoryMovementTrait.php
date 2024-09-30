<?php

namespace App\Traits\WMS;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use App\Models\WMS\Storage\QueuedSubLocationModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use Exception;
use App\Traits\ResponseTrait;
use DB;

trait InventoryMovementTrait
{
    use ResponseTrait;

    public function onGetReceiveItems($createdAt, $statusType)
    {
        try {
            $warehouseReceiving = WarehouseReceivingModel::whereDate('created_at', $createdAt)->first();
            if ($warehouseReceiving) {
                $quantity = $warehouseReceiving->quantity;
                $receivingQuantity = $warehouseReceiving->received_quantity;
                $substandardQuantity = $warehouseReceiving->substandard_quantity;
                $forReceiveQuantity = 0;
                if (strcasecmp($statusType, 'to receive') == 0) {
                    $forReceiveQuantity = $quantity - ($receivingQuantity + $substandardQuantity);
                }
                // Add other conditions here

                return $forReceiveQuantity;
            } else {
                return 0;
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Inventory Movement ' . __('msg.record_not_found'));
        }
    }

    // Add other methods for Put Away, Stock Transfer, and Distribution here

    public function onGetZoneStoredItems($zoneId, $isTransferAll = false)
    {
        try {
            $subLocationModel = SubLocationModel::where([
                'zone_id' => $zoneId,
                'status' => 1,
                'is_permanent' => 1
            ])
                ->get();
            if (count($subLocationModel) > 0) {
                $zoneItems = [];
                foreach ($subLocationModel as $subLocation) {
                    $hasLayer = $subLocation->has_layer;
                    $layerItems = [];
                    $hasItems = false;

                    if ($hasLayer == 1) {
                        $subLocationLayers = json_decode($subLocation->layers, true);
                        // Different Layer Looping per sub-location
                        foreach ($subLocationLayers as $layers) {
                            $queuedPermanentStorage = QueuedSubLocationModel::where([
                                'sub_location_id' => $subLocation->id,
                                'layer_level' => $layers['layer_no']
                            ])
                                ->orderBy('id', 'DESC')
                                ->first();

                            $hasStoredItems = count(json_decode($queuedPermanentStorage->production_items, true)) > 0;
                            if ($hasStoredItems) {
                                // Different Item Looping per layer
                                $hasItems = true;
                                $layerProductionItems = json_decode($queuedPermanentStorage->production_items, true);
                                foreach ($layerProductionItems as $storedItems) {
                                    $itemMasterdataModel = ItemMasterdataModel::find($storedItems['item_id']);
                                    $itemCode = $itemMasterdataModel->item_code;
                                    $currentLayerItems = $storedItems['item_id'] . '-' . $layers['layer_no'];
                                    if (isset($layerItems[$currentLayerItems])) {
                                        $layerItems[$currentLayerItems]['initial_stock'] += 1;
                                        if ($isTransferAll) {
                                            $layerItems[$storedItems['item_id']]['transfer_quantity'] += 1;
                                        }
                                    } else {
                                        $transferQuantity = $isTransferAll ? 1 : 0;

                                        $layerItems[$currentLayerItems] = [
                                            'item_id' => $storedItems['item_id'],
                                            'item_code' => $itemCode,
                                            'item_description' => $itemMasterdataModel->description,
                                            'layer' => $layers['layer_no'],
                                            'initial_stock' => 1,
                                            'transfer_quantity' => $transferQuantity
                                        ];
                                    }
                                }
                            }
                        }
                    }

                    if ($hasItems) {
                        $zoneItems[] = [
                            'sub_location_id' => $subLocation->id,
                            'code' => $subLocation->code,
                            'layers' => $layerItems
                        ];
                    }

                }
            }
            return $zoneItems;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onGetOccupiedZones()
    {
        try {
            $zoneModel = ZoneModel::where('status', 1)->get();
            $zoneList = [];
            foreach ($zoneModel as $zone) {
                $subLocationModel = SubLocationModel::where([
                    'zone_id' => $zone->id,
                    'status' => 1,
                    'is_permanent' => 1
                ])
                    ->get();
                if (count($subLocationModel) > 0) {
                    foreach ($subLocationModel as $subLocation) {
                        $hasLayer = $subLocation->has_layer;
                        $hasItems = false;
                        if (!$hasItems) {
                            if ($hasLayer == 1) {
                                $subLocationLayers = json_decode($subLocation->layers, true);
                                foreach ($subLocationLayers as $layers) {
                                    $queuedPermanentStorage = QueuedSubLocationModel::where([
                                        'sub_location_id' => $subLocation->id,
                                        'layer_level' => $layers['layer_no']
                                    ])
                                        ->orderBy('id', 'DESC')
                                        ->first();
                                    if ($queuedPermanentStorage) {
                                        $zoneList[] = [
                                            'zone_id' => $zone->id,
                                            'zone_name' => $zone->long_name,
                                            'location_type' => $zone->storageType->short_name,
                                        ];
                                        $hasItems = true;
                                        break;
                                    }
                                }
                            }
                        }

                    }
                }
            }

            return $zoneList;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}

