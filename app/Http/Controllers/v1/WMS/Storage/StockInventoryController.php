<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use App\Models\WMS\Storage\StockInventoryModel;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class StockInventoryController extends Controller
{
    use WmsCrudOperationsTrait;
    public function onGetByItemCode($item_code)
    {
        try {
            $stockInventoryModel = StockInventoryModel::where([
                'item_code' => $item_code
            ])->first();

            $itemMasterdata = $stockInventoryModel->itemMasterdata;
            // $productionBatchCount = ProductionBatchModel::where('item_code', $item_code)
            //     ->where('status', '!=', 3)
            //     ->get();

            // $stockInventoryModel->batch_count = $productionBatchCount;
            $data = [
                'item_details' => $itemMasterdata,
                'stock_inventory_details' => $stockInventoryModel->getAttributes()
            ];
            return $this->dataResponse('success', 200, 'Stock Inventory ' . __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required',
            'is_overwrite' => 'required|boolean', // 0 , 1
            'is_add_quantity' => 'required|boolean', // 0 , 1
        ]);
        try {
            DB::beginTransaction();
            $bulkUploadData = json_decode($request['bulk_data'], true);
            $createdById = $request['created_by_id'];

            $dataToBeOverwritten = [];
            foreach ($bulkUploadData as $data) {
                $itemCodeExist = StockInventoryModel::where('item_code', $data['item_code'])->exists();
                if ($itemCodeExist) {
                    if ($fields['is_overwrite']) {
                        if ($fields['is_add_quantity']) {
                            $stockInventory = StockInventoryModel::where('item_code', $data['item_code'])->first();
                            $stockInventory->stock_count = ($stockInventory->stock_count + $data['stock_count']);
                            $stockInventory->save();
                        } else {
                            $stockInventory = StockInventoryModel::where('item_code', $data['item_code'])->first();
                            $stockInventory->stock_count = $data['stock_count'];
                            $stockInventory->save();
                        }
                    } else {
                        $dataToBeOverwritten[] = $data['item_code'];
                        continue;
                    }
                } else {
                    $record = new StockInventoryModel();
                    $record->fill($data);
                    $record->created_by_id = $createdById;
                    $record->save();
                }

            }
            DB::commit();
            return $this->dataResponse('success', 201, 'Stock Inventory ' . __('msg.create_success'), $dataToBeOverwritten);
        } catch (Exception $exception) {
            DB::rollback();
            if ($exception instanceof \Illuminate\Database\QueryException && $exception->errorInfo[1] == 1364) {
                preg_match("/Field '(.*?)' doesn't have a default value/", $exception->getMessage(), $matches);
                return $this->dataResponse('error', 400, __('Field ":field" requires a default value.', ['field' => $matches[1] ?? 'unknown field']));
            }
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onUpdate(Request $request, $id)
    {
        $rules = [
            'stock_count' => 'required'
        ];
        return $this->updateRecordById(StockInventoryModel::class, $request, $rules, 'Stock Inventory', $id);
    }

    public function onGetAll()
    {
        return $this->readCurrentRecord(ItemMasterdataModel::class, null, null, 'stockInventories', null, 'Stock Inventory');
    }

    public function onGetInStock($item_code)
    {
        try {
            $productionBatchModel = ProductionBatchModel::where('item_code', $item_code)
                ->where('status', '!=', 3)
                ->get();

            $inStockArray = [];
            if (count($productionBatchModel) > 0) {
                foreach ($productionBatchModel as $productionBatch) {
                    $productionItems = json_decode($productionBatch->productionItems->produced_items, true);

                    foreach ($productionItems as $productionItem) {
                        if ($productionItem['status'] == 13 && $productionItem['sticker_status'] == 1) {
                            $inStockData = [
                                'batch_no' => $productionBatch->batch_number,
                                'sticker_no' => $productionItem['batch_code'],
                                'production_date' => $productionBatch->productionOrder->production_date,
                                'content_quantity' => $productionItem['q']
                            ];
                            $inStockArray[] = $inStockData;
                        }
                    }
                }
            }
            return $this->dataResponse('success', 200, 'In Stock ' . __('msg.record_found'), $inStockArray);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetStockAllLocation($item_code)
    {
        try {
            $productionBatchModel = ProductionBatchModel::where('item_code', $item_code)
                ->where('status', '!=', 3)
                ->get();

            $warehouseLocations = [];
            if (count($productionBatchModel) > 0) {
                foreach ($productionBatchModel as $productionBatch) {
                    $productionItems = json_decode($productionBatch->productionItems->produced_items, true);

                    foreach ($productionItems as $productionItem) {
                        if ($productionItem['status'] == 13 && $productionItem['sticker_status'] == 1 && isset($productionItem['sub_location'])) {
                            $subLocationId = $productionItem['sub_location']['sub_location_id'];
                            $layerLevel = $productionItem['sub_location']['layer_level'];
                            $subLocationModel = SubLocationModel::find($subLocationId);
                            $zoneId = $subLocationModel->zone_id;
                            $warehouseKey = "Z${zoneId}-SL${subLocationId}-L${layerLevel}";

                            if (isset($warehouseLocations[$warehouseKey])) {
                                $warehouseLocations[$warehouseKey]['quantity'] += 1;
                            } else {
                                $warehouseLocations[$warehouseKey] = [
                                    'warehouse_key' => $warehouseKey,
                                    'zone' => $subLocationModel->zone->short_name,
                                    'sub_location' => $subLocationModel->code,
                                    'layer_level' => $layerLevel,
                                    'quantity' => 1
                                ];
                            }
                        }
                    }
                }
            }
            return $this->dataResponse('success', 200, 'Stock Inventory ' . __('msg.record_found'), array_values($warehouseLocations));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetAllZoneLocation()
    {
        try {
            $productionBatchModel = ProductionBatchModel::where('status', '!=', 3)->get();

            $zoneLocationList = [];
            if (count($productionBatchModel) > 0) {
                foreach ($productionBatchModel as $productionBatch) {
                    $productionItems = json_decode($productionBatch->productionItems->produced_items, true);

                    foreach ($productionItems as $productionItem) {
                        if ($productionItem['status'] == 13 && $productionItem['sticker_status'] == 1) {
                            $subLocationId = $productionItem['sub_location']['sub_location_id'];
                            $subLocationModel = SubLocationModel::find($subLocationId);
                            $zoneId = $subLocationModel->zone_id;

                            if (!isset($zoneLocationList[$zoneId]['sku'])) {
                                $zoneLocationList[$zoneId]['zone_details'] = [
                                    'id' => $zoneId,
                                    'short_name' => $subLocationModel->zone->long_name,
                                    'long_name' => $subLocationModel->zone->long_name,
                                    'code' => $subLocationModel->zone->code,
                                    'storage_type' => $subLocationModel->zone->storageType->short_name,
                                    'sub_location_count' => $subLocationModel->zone->subLocations->count()

                                ];
                                $zoneLocationList[$zoneId]['sku'] = [];
                            }

                            if (isset($zoneLocationList[$zoneId]['sku'][$productionBatch->item_code])) {
                                $zoneLocationList[$zoneId]['sku'][$productionBatch->item_code] += 1;
                            } else {
                                $zoneLocationList[$zoneId]['sku'][$productionBatch->item_code] = 1;
                            }
                        }
                    }
                }
            }
            return $this->dataResponse('success', 200, 'Stock Inventory ' . __('msg.record_found'), array_values($zoneLocationList));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetZoneDetails($zone_id, $item_code = null)
    {
        try {
            $zoneModel = ZoneModel::find($zone_id);
            $zoneDetails = [
                'zone_details' => [
                    'id' => $zoneModel->id,
                    'short_name' => $zoneModel->long_name,
                    'long_name' => $zoneModel->long_name,
                    'code' => $zoneModel->code,
                    'storage_type' => $zoneModel->storageType->short_name,
                    'sub_location_count' => $zoneModel->subLocations->count(),
                    'warehouse_location' => $zoneModel->warehouse->long_name,
                ],
                'quantity_on_hand' => 0,
            ];
            $productionBatchModel = ProductionBatchModel::where('status', '!=', 3)->get();

            $skuList = [
                'all' => [
                    'title' => 'All',
                    'api' => "item/stock/inventory/zone/item/get/{$zone_id}",
                ]
            ];
            if (count($productionBatchModel) > 0) {
                foreach ($productionBatchModel as $productionBatch) {
                    $productionItems = json_decode($productionBatch->productionItems->produced_items, true);

                    foreach ($productionItems as $productionItem) {
                        if ($productionItem['status'] == 13 && $productionItem['sticker_status'] == 1) {
                            $subLocationId = $productionItem['sub_location']['sub_location_id'];
                            $subLocationModel = SubLocationModel::find($subLocationId);
                            $zoneId = $subLocationModel->zone_id;
                            $isItemCodeFilter = $item_code ? $productionBatch->item_code == $item_code : true;
                            if ($zoneId == $zone_id) {
                                if (!array_key_exists($productionBatch->item_code, $skuList)) {
                                    $skuList[$productionBatch->item_code] = [
                                        'title' => $productionBatch->item_code,
                                        'api' => "item/stock/inventory/zone/item/get/{$zone_id}/{$productionBatch->item_code}",
                                    ];
                                }
                                if ($isItemCodeFilter) {
                                    $zoneDetails['quantity_on_hand'] += 1;
                                }
                            }
                        }
                    }
                }
                $zoneDetails['sku'] = array_values($skuList);
            }
            return $this->dataResponse('success', 200, 'Stock Inventory ' . __('msg.record_found'), $zoneDetails);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetZoneItemList($zone_id, $item_code = null)
    {
        try {
            $productionBatchModel = ProductionBatchModel::where('status', '!=', 3);
            if ($item_code) {
                $productionBatchModel->where('item_code', $item_code);
            }
            $productionBatchModel = $productionBatchModel->get();

            $zoneItemList = [];
            if (count($productionBatchModel) > 0) {
                foreach ($productionBatchModel as $productionBatch) {
                    $productionItems = json_decode($productionBatch->productionItems->produced_items, true);

                    foreach ($productionItems as $productionItem) {
                        if ($productionItem['status'] == 13 && $productionItem['sticker_status'] == 1) {
                            $itemCode = $productionBatch->item_code;
                            $subLocationId = $productionItem['sub_location']['sub_location_id'];
                            $layerLevel = $productionItem['sub_location']['layer_level'];
                            $subLocationModel = SubLocationModel::find($subLocationId);
                            $zoneId = $subLocationModel->zone_id;
                            $zoneItemKey = "${itemCode}-SL${subLocationId}-L${layerLevel}";

                            if ($zoneId == $zone_id) {
                                if (isset($zoneItemList[$zoneItemKey])) {
                                    $zoneItemList[$zoneItemKey]['quantity'] += 1;
                                    if (!in_array($productionItem['bid'], $zoneItemList[$zoneItemKey]['batch_array'])) {
                                        $zoneItemList[$zoneItemKey]['batch_array'][] = $productionItem['bid'];
                                    }
                                } else {
                                    $zoneItemList[$zoneItemKey] = [
                                        'zone_item_key' => $zoneItemKey,
                                        'item_code' => $itemCode,
                                        'batch_no' => $productionBatch->batch_number,
                                        'sub_location' => $subLocationModel->code,
                                        'layer_level' => "L${layerLevel}",
                                        'batch_array' => [$productionItem['bid']],
                                        'quantity' => 1
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            return $this->dataResponse('success', 200, 'Stock Inventory ' . __('msg.record_found'), array_values($zoneItemList));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    #region status list
    // 0 => 'Good',
    // 1 => 'On Hold',
    // 1.1 => 'On Hold - Sub Standard
    // 2 => 'For Receive',
    // 2.1 => 'For Receive - Inbound',
    // 3 => 'Received',
    // 3.1 => 'For Put-away - In Process',
    // 4 => 'For Investigation',
    // 5 => 'For Sampling',
    // 6 => 'For Retouch',
    // 7 => 'For Slice',
    // 8 => 'For Sticker Update',
    // 9 => 'Sticker Updated',
    // 10 => 'Reviewed',
    // 11 => 'Retouched',
    // 12 => 'Sliced',
    // 13 => 'Stored',
    #endregion
}
