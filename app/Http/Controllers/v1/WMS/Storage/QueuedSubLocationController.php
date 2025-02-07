<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\MOS\Production\ProductionItemController;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Storage\StockLogModel;
use App\Models\WMS\Warehouse\WarehouseForPutAwayModel;
use App\Models\WMS\Warehouse\WarehouseForPutAwayV2Model;
use App\Models\WMS\Warehouse\WarehousePutAwayModel;
use App\Traits\ResponseTrait;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use Exception;
use DB;

class QueuedSubLocationController extends Controller
{
    use QueueSubLocationTrait, ResponseTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'warehouse_put_away_id' => 'required|exists:wms_warehouse_put_away,id',
            'item_id' => 'required|exists:wms_item_masterdata,id',
            'created_by_id' => 'required',
            'storage_full_scanned_items' => 'nullable|json'
        ]);
        try {
            $warehouseForPutAway = WarehouseForPutAwayModel::where([
                'warehouse_put_away_id' => $fields['warehouse_put_away_id'],
                'item_id' => $fields['item_id'],
                'status' => 1
            ])->first();
            if ($warehouseForPutAway && $warehouseForPutAway->sub_location_id) {
                DB::beginTransaction();
                $createdById = $fields['created_by_id'];
                $subLocationId = $warehouseForPutAway->sub_location_id;
                $layerLevel = $warehouseForPutAway->layer_level;
                $warehouseForPutAwayProductionItems = json_decode($warehouseForPutAway->production_items, true);
                $scannedItems = null;
                if (isset($fields['storage_full_scanned_items'])) {
                    $scannedItems = json_decode($fields['storage_full_scanned_items'], true);
                    $this->onUpdateItemStatus($warehouseForPutAway, $fields['storage_full_scanned_items'], $createdById);
                } else {
                    $scannedItems = json_decode($warehouseForPutAway->transfer_items, true);
                }
                $this->onUpdatePutAway($warehouseForPutAway, $scannedItems);
                $this->onQueueSubLocation($createdById, $scannedItems, $warehouseForPutAwayProductionItems, $subLocationId, $layerLevel, $warehouseForPutAway->warehouse_receiving_reference_number);
                DB::commit();
            } else {
                return $this->dataResponse('success', 200, __('msg.record_not_found'));
            }
            return $this->dataResponse('success', 200, __('msg.create_success'));


        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onUpdateItemStatus($warehouseForPutAway, $scannedItems, $createdById)
    {
        try {
            $warehousePutAwayModel = $warehouseForPutAway->warehousePutAway;
            $warehouseProductionItem = json_decode($warehousePutAwayModel->production_items, true);
            $toBeDecoded = json_decode($scannedItems, true);
            foreach ($toBeDecoded as $items) {
                foreach ($warehouseProductionItem as &$warehouseItems) {
                    if ($warehouseItems['sticker_no'] == $items['sticker_no'] && $warehouseItems['bid'] == $items['bid']) {
                        $warehouseItems['status'] = 3.1;
                    }
                    unset($warehouseItems);
                }
            }

            $warehousePutAwayModel->production_items = json_encode($warehouseProductionItem);
            $warehousePutAwayModel->save();
            $productionItemController = new ProductionItemController();
            $productionItemRequest = new Request([
                'created_by_id' => $createdById,
                'scanned_item_qr' => $scannedItems,
                'status_id' => 3.1,
            ]);
            $productionItemController->onChangeStatus($productionItemRequest);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onUpdatePutAway($warehouseForPutAway, $scannedItems)
    {
        try {
            $warehouseForPutAwayItems = json_decode($warehouseForPutAway->production_items, true);

            $warehousePutAwayModel = WarehousePutAwayModel::find($warehouseForPutAway->warehouse_put_away_id);
            $discrepancyDataPutAway = json_decode($warehousePutAwayModel->discrepancy_data, true);
            $remainingQuantity = json_decode($warehousePutAwayModel->remaining_quantity, true);
            $transferredQuantity = json_decode($warehousePutAwayModel->transferred_quantity, true);
            foreach ($scannedItems as &$scannedValue) {
                foreach ($warehouseForPutAwayItems as $key => $warehouseForPutAwayItem) {
                    if ($scannedValue['sticker_no'] == $warehouseForPutAwayItem['sticker_no']) {
                        $productionBatch = ProductionBatchModel::find($warehouseForPutAwayItem['bid']);

                        $productionItemStatus = json_decode($productionBatch->productionItems->produced_items, true)[$scannedValue['sticker_no']]['status'];
                        if ($productionItemStatus != '3.1') {
                            continue;
                        }

                        foreach ($discrepancyDataPutAway as $keyDiscrepancy => $item) {
                            if ($item['bid'] == $warehouseForPutAwayItem['bid'] && $item['sticker_no'] == $warehouseForPutAwayItem['sticker_no']) {
                                unset($discrepancyDataPutAway[$keyDiscrepancy]);
                                break;
                            }
                        }

                        $itemMasterdata = $productionBatch->productionOta->itemMasterdata ?? $productionBatch->productionOtb->itemMasterdata;
                        $primaryUom = $itemMasterdata->uom->long_name ?? null;
                        $primaryConversion = $itemMasterdata->primaryConversion->long_name ?? null;

                        if ($primaryUom) {
                            if (!isset($remainingQuantity[$primaryUom])) {
                                $remainingQuantity[$primaryUom] = 0;
                            }
                            if (!isset($transferredQuantity[$primaryUom])) {
                                $transferredQuantity[$primaryUom] = 0;
                            }
                            $remainingQuantity[$primaryUom]--;
                            $transferredQuantity[$primaryUom]++;
                        }

                        if ($primaryConversion) {
                            if (!isset($remainingQuantity[$primaryConversion])) {
                                $remainingQuantity[$primaryConversion] = 0;
                            }
                            if (!isset($transferredQuantity[$primaryConversion])) {
                                $transferredQuantity[$primaryConversion] = 0;
                            }
                            $remainingQuantity[$primaryConversion] -= intval($warehouseForPutAwayItem['q']);
                            $transferredQuantity[$primaryConversion] += intval($warehouseForPutAwayItem['q']);
                        }

                        unset($warehouseForPutAwayItems[$key]); // Keep this as is
                    }
                }

            }

            $encodedPutAwayItems = count($warehouseForPutAwayItems) > 0 ? json_encode(array_values($warehouseForPutAwayItems)) : null;

            $warehousePutAwayModel->transferred_quantity = json_encode($transferredQuantity);
            $warehousePutAwayModel->remaining_quantity = json_encode($remainingQuantity);
            $warehousePutAwayModel->discrepancy_data = json_encode(array_values($discrepancyDataPutAway));
            $warehousePutAwayModel->save();
            if ($encodedPutAwayItems != null) {
                $warehouseForPutAway->production_items = $encodedPutAwayItems;
                $warehouseForPutAway->transfer_items = null;
                $warehouseForPutAway->save();
            } else {
                // Warehouse Receive if already no more item to be received
                // $warehouseReceivingValue->status = 1;
                // $warehouseReceivingValue->updated_by_id = $createdById;
                // $warehouseReceivingValue->discrepancy_data = json_encode($discrepancy);
                // $warehouseReceivingValue->save();
                // $this->createWarehouseLog(ProductionItemModel::class, $productionItemModel->id, WarehouseReceivingModel::class, $warehouseReceivingValue->id, $warehouseReceivingValue->getAttributes(), $createdById, 1);
                $warehouseForPutAway->delete();
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onGetCurrent($put_away_key)
    {
        #region old code remains here RIP....
        /*
        try {
            $warehouseForPutAway = WarehouseForPutAwayModel::where([
                'item_id' => $item_id,
                'sub_location_id' => $sub_location_id,
                'status' => 1
            ])->first();
            if ($warehouseForPutAway) {
                $subLocationDetails = $this->onGetSubLocationDetails($sub_location_id, $warehouseForPutAway->layer_level, true);
                $productionItems = json_decode($warehouseForPutAway->production_items, true);
                $restructuredArray = [];
                foreach ($productionItems as $item) {
                    $productionItemDetails = ProductionItemModel::where('production_batch_id', $item['bid'])->first();
                    $itemDetails = json_decode($productionItemDetails->produced_items, true);

                    $batchCode = $itemDetails[$item['sticker_no']]['batch_code'];
                    $restructuredArray[$batchCode] = $item;
                }

                $subLocationDetails['production_items'] = $restructuredArray;
                return $this->dataResponse('success', 200, __('msg.record_found'), $subLocationDetails);

            }
            return $this->dataResponse('success', 200, __('msg.record_not_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
        */
        #endregion
        try {
            $putAwayItemsArr = [];

            $warehouseForPutAwayV2Model = WarehouseForPutAwayV2Model::where('warehouse_put_away_key', $put_away_key)->orderBy('id', 'DESC')->first();
            if ($warehouseForPutAwayV2Model) {
                $subLocationDetails = $this->onGetSubLocationDetails($warehouseForPutAwayV2Model->sub_location_id, $warehouseForPutAwayV2Model->layer_level, true);
                $productionItems = json_decode($warehouseForPutAwayV2Model->production_items, true) ?? [];
                $restructuredArray = [];

                foreach ($productionItems as $item) {
                    $productionItemDetails = ProductionItemModel::where('production_batch_id', $item['bid'])->first();
                    $itemDetails = json_decode($productionItemDetails->produced_items, true);

                    $batchCode = $itemDetails[$item['sticker_no']]['batch_code'];
                    $restructuredArray[$batchCode] = $item;
                }

                $warehousePutAwayKey = explode('-', $put_away_key);
                $warehousePutAwayItems = WarehousePutAwayModel::where([
                    'warehouse_receiving_reference_number' => $warehousePutAwayKey[0],
                    'item_id' => $warehousePutAwayKey[1],
                ]);

                if (isset($warehousePutAwayKey[2])) {
                    $warehousePutAwayItems->where('temporary_storage_id', $warehousePutAwayKey[2]);
                }
                $warehousePutAwayItems = $warehousePutAwayItems->get();

                foreach ($warehousePutAwayItems as $putAwayItems) {
                    $productionItems = json_decode($putAwayItems['production_items'], true);
                    foreach ($productionItems as $prodItem) {
                        $productionBatchModel = ProductionBatchModel::find($prodItem['bid']);
                        $itemMasterdataModel = $productionBatchModel->itemMasterdata;

                        $itemCode = $itemMasterdataModel->item_code;
                        $itemId = $itemMasterdataModel->id;
                        $putAwayItemsArr[] = [
                            'bid' => $prodItem['bid'],
                            'item_code' => $itemCode,
                            'item_id' => $itemId,
                            'q' => $prodItem['q'],
                            'sticker_no' => $prodItem['sticker_no']
                        ];
                    }
                }
                $subLocationDetails['put_away_items'] = $putAwayItemsArr;
                $subLocationDetails['production_items'] = $restructuredArray;
                return $this->dataResponse('success', 200, __('msg.record_found'), $subLocationDetails);
            }
            return $this->dataResponse('success', 200, __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetItems($sub_location_id, $status)
    {
        try {
            $items = $this->onGetQueuedItems($sub_location_id, true);
            $combinedItems = array_merge(...$items);
            $data = [
                'warehouse' => null,
                'production_items' => []
            ];
            foreach ($combinedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $itemId = $productionOrderToMake->itemMasterdata->id;
                $stickerNumber = $itemDetails['sticker_no'];
                $producedItem = json_decode($productionBatch->productionItems->produced_items, true)[$stickerNumber];
                $warehouse = $producedItem['warehouse'];
                if ($data['warehouse'] === null) {
                    $data['warehouse'] = $warehouse;
                }
                if ($producedItem['status'] == $status) {
                    $data['production_items'][] = [
                        'bid' => $itemDetails['bid'],
                        'item_code' => $itemCode,
                        'item_id' => $itemId,
                        'sticker_no' => $stickerNumber,
                        'q' => $producedItem['q']
                    ];
                }
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onQueueSubLocation($createdById, $scannedItems, $warehouseForPutAwayProductionItems, $subLocationId, $layerLevel, $referenceNumber)
    {
        try {
            $itemsPerBatchArr = [];
            foreach ($scannedItems as $scannedValue) {
                $stickerNumber = $scannedValue['sticker_no'];
                $batchId = $scannedValue['bid'];

                $flag = $this->onCheckScannedItems($warehouseForPutAwayProductionItems, $stickerNumber, $batchId);

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

    public function onCheckScannedItems($scannedItems, $stickerNumber, $batchId)
    {
        try {
            foreach ($scannedItems as $value) {
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
}
