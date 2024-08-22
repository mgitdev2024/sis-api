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

            $arrayWarehouseLocation = array_values($warehouseLocations);
            return $this->dataResponse('success', 200, 'Stock Inventory ' . __('msg.record_found'), $arrayWarehouseLocation);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetAllZoneLocation()
    {
        try {
            $productionBatchModel = ProductionBatchModel::where('status', '!=', 3)
                ->get();

            $zoneLocation = [];
            if (count($productionBatchModel) > 0) {
                foreach ($productionBatchModel as $productionBatch) {
                    $productionItems = json_decode($productionBatch->productionItems->produced_items, true);

                    foreach ($productionItems as $productionItem) {
                        if ($productionItem['status'] == 13 && $productionItem['sticker_status'] == 1) {
                            $subLocationId = $productionItem['sub_location']['sub_location_id'];
                            $subLocationModel = SubLocationModel::find($subLocationId);
                            $zoneId = $subLocationModel->zone_id;

                            if (isset($zoneLocation[$zoneId])) {
                                $zoneLocation[$zoneId]['sku_quantity'] += 1;
                            } else {
                                $zoneLocation[$zoneId] = [
                                    'sku_quantity' => 1,
                                ];
                            }
                        }
                    }
                }
            }
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
