<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\QualityAssurance\SubStandardItemController;
use App\Http\Controllers\v1\WMS\Storage\QueuedSubLocationController;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Storage\QueuedSubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use App\Models\WMS\Warehouse\WarehouseForPutAwayModel;
use App\Models\WMS\Warehouse\WarehouseForPutAwayV2Model;
use App\Models\WMS\Warehouse\WarehousePutAwayModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use App\Traits\MOS\ProductionLogTrait;
use App\Traits\WMS\WarehouseLogTrait;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use Exception;
use DB;

class WarehousePutAwayController extends Controller
{

    use WmsCrudOperationsTrait, ProductionLogTrait, WarehouseLogTrait;
    public function getRules()
    {
        return [
            'created_by_id' => 'required',
            'warehouse_receiving_reference_number' => 'required|exists:wms_warehouse_receiving,reference_number',
            'item_code' => 'required|exists:wms_item_masterdata,item_code',
            'production_items' => 'required',
            'remaining_quantity' => 'required',
        ];
    }

    #region Create Put Away
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'warehouse_receiving_reference_number' => 'required|exists:wms_warehouse_receiving,reference_number',
            'item_id' => 'required|exists:wms_item_masterdata,id',
            'production_items' => 'required|json',
            'received_quantity' => 'required|integer',
            'scanned_items' => 'required|json',
            'temporary_storage_id' => 'nullable'
        ]);
        try {
            DB::beginTransaction();
            $warehousePutAwayModel = WarehousePutAwayModel::where('warehouse_receiving_reference_number', $fields['warehouse_receiving_reference_number'])
                ->where('item_id', $fields['item_id'])
                ->where('status', 0)
                ->first();

            if ($warehousePutAwayModel) {
                $this->onUpdatePutAway($fields, $warehousePutAwayModel);
            } else {
                $this->onInitialPutAway($fields);
            }

            DB::commit();
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }

    }
    public function onUpdatePutAway($fields, $warehousePutAwayModel)
    {
        try {
            $productionItems = json_decode($fields['production_items'], true);
            $inclusionArray = ['2.1'];
            $remainingQuantity = null;
            $receivedQuantity = null;
            $remainingPieces = 0;
            $currentWarehouseItems = json_decode($warehousePutAwayModel->production_items, true);
            $currentDiscrepancyData = json_decode($warehousePutAwayModel->discrepancy_data, true);
            foreach ($productionItems as &$value) {
                $checkItemScanned = $this->onCheckScannedItems(json_decode($fields['scanned_items'], true), $value['sticker_no'], $value['bid']);
                if ($checkItemScanned && in_array($value['status'], $inclusionArray)) {
                    $productionBatch = ProductionBatchModel::find($value['bid']);
                    $itemMasterdata = $productionBatch->productionOta->itemMasterdata ?? $productionBatch->productionOtb->itemMasterdata;
                    $primaryUom = $itemMasterdata->uom->long_name ?? null;
                    $primaryConversion = $itemMasterdata->primaryConversion->long_name ?? null;
                    $produceItemModel = $productionBatch->productionItems;
                    $producedItems = json_decode($produceItemModel->produced_items, true);

                    $receivedQuantity = json_decode($warehousePutAwayModel->received_quantity, true) ?? [];
                    $remainingQuantity = json_decode($warehousePutAwayModel->remaining_quantity, true) ?? [];
                    $primaryPackingSize = $itemMasterdata->primary_item_packing_size == null || $itemMasterdata->primary_item_packing_size <= 0 ? 1 : $itemMasterdata->primary_item_packing_size;
                    if ($primaryUom) {
                        $remainingPieces += $producedItems[$value['sticker_no']]['q'];
                        if (!isset($remainingQuantity[$primaryUom])) {
                            $remainingQuantity[$primaryUom] = 0;
                        }
                        if (!isset($remainingQuantity[$primaryUom])) {
                            $receivedQuantity[$primaryUom] = 0;
                        }

                        if ($remainingPieces >= $primaryPackingSize || $receivedQuantity >= $primaryPackingSize) {
                            if ($receivedQuantity >= $primaryPackingSize) {
                                $receivedQuantity[$primaryUom]++;

                            }
                            if ($remainingPieces >= $primaryPackingSize) {
                                $remainingQuantity[$primaryUom]++;
                            }
                            $remainingPieces -= $primaryPackingSize;
                        }
                    }
                    $value['status'] = 3;
                    $currentWarehouseItems[] = $value;
                    $currentDiscrepancyData[] = $value;

                    $producedItems[$value['sticker_no']]['status'] = 3; // Received
                    $produceItemModel->produced_items = json_encode($producedItems);
                    $produceItemModel->save();
                    $this->createProductionLog(ProductionItemModel::class, $produceItemModel->id, $producedItems[$value['sticker_no']], $fields['created_by_id'], 1, $value['sticker_no']);

                    $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $warehousePutAwayModel->warehouse_receiving_reference_number)
                        ->where('production_order_id', $productionBatch->production_order_id)
                        ->where('batch_number', $productionBatch->batch_number)
                        ->where('item_code', $itemMasterdata->item_code)
                        ->first();
                    $warehouseReceivingProductionItems = json_decode($warehouseReceiving->produced_items, true);
                    $warehouseReceivingProductionItems[$value['sticker_no']]['status'] = 3; // Received
                    $warehouseReceiving->produced_items = json_encode($warehouseReceivingProductionItems);
                    $warehouseReceiving->save();
                    $this->createWarehouseLog(ProductionItemModel::class, $produceItemModel->id, WarehouseReceivingModel::class, $warehouseReceiving->id, $warehouseReceiving->getAttributes(), $fields['created_by_id'], 1);
                }
                unset($value);
            }
            $remainingPrimaryConversionUnitExtraction = array_keys($remainingQuantity)[0];
            if ($remainingPieces > 0) {
                $remainingQuantity[$remainingPrimaryConversionUnitExtraction] .= ".$remainingPieces";
            }

            $warehousePutAwayModel->production_items = json_encode($currentWarehouseItems);
            $warehousePutAwayModel->discrepancy_data = json_encode($currentDiscrepancyData);

            $warehousePutAwayModel->received_quantity = json_encode($receivedQuantity);
            $warehousePutAwayModel->remaining_quantity = json_encode($remainingQuantity);
            $warehousePutAwayModel->save();
            $this->createWarehouseLog(null, null, WarehouseReceivingModel::class, $warehousePutAwayModel->id, $warehousePutAwayModel->getAttributes(), $fields['created_by_id'], 1);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
    public function onInitialPutAway($fields)
    {
        try {
            $warehouseReceivingReferenceNumber = $fields['warehouse_receiving_reference_number'];
            $referenceNumber = WarehousePutAwayModel::onGenerateWarehousePutAwayReferenceNumber($warehouseReceivingReferenceNumber);
            $productionItems = json_decode($fields['production_items'], true);
            $remainingQuantity = [];
            $remainingPieces = 0;
            foreach ($productionItems as &$value) {
                $flag = $this->onCheckScannedItems(json_decode($fields['scanned_items'], true), $value['sticker_no'], $value['bid']);
                if ($flag) {
                    $productionBatch = ProductionBatchModel::find($value['bid']);
                    $itemMasterdata = $productionBatch->productionOta->itemMasterdata ?? $productionBatch->productionOtb->itemMasterdata;
                    $primaryUom = $itemMasterdata->uom->long_name ?? null;
                    $primaryConversion = $itemMasterdata->primaryConversion->long_name ?? null;
                    $produceItemModel = $productionBatch->productionItems;
                    $producedItems = json_decode($produceItemModel->produced_items, true);
                    $primaryPackingSize = $itemMasterdata->primary_item_packing_size == null || $itemMasterdata->primary_item_packing_size <= 0 ? 1 : $itemMasterdata->primary_item_packing_size;
                    if ($primaryUom) {
                        $remainingPieces += $producedItems[$value['sticker_no']]['q'];
                        if (!isset($remainingQuantity[$primaryUom])) {
                            $remainingQuantity[$primaryUom] = 0;
                        }

                        if ($remainingPieces >= $primaryPackingSize) {
                            $remainingQuantity[$primaryUom]++;
                            $remainingPieces -= $primaryPackingSize;
                        }
                    }

                    // if ($primaryConversion) {
                    //     if (!isset($remainingQuantity[$primaryConversion])) {
                    //         $remainingQuantity[$primaryConversion] = 0;
                    //     }
                    //     $remainingQuantity[$primaryConversion] += intval($producedItems[$value['sticker_no']]['q']);
                    // }

                    $value['status'] = 3;
                    $producedItems[$value['sticker_no']]['status'] = 3; // Received
                    $produceItemModel->produced_items = json_encode($producedItems);
                    $produceItemModel->save();
                    $this->createProductionLog(ProductionItemModel::class, $produceItemModel->id, $producedItems[$value['sticker_no']], $fields['created_by_id'], 1, $value['sticker_no']);

                    $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $warehouseReceivingReferenceNumber)
                        ->where('production_order_id', $productionBatch->production_order_id)
                        ->where('batch_number', $productionBatch->batch_number)
                        ->where('item_code', $itemMasterdata->item_code)
                        ->first();
                    $warehouseReceivingProductionItems = json_decode($warehouseReceiving->produced_items, true);
                    $warehouseReceivingProductionItems[$value['sticker_no']]['status'] = 3; // Received
                    $warehouseReceiving->produced_items = json_encode($warehouseReceivingProductionItems);
                    $warehouseReceiving->save();
                    $this->createWarehouseLog(ProductionItemModel::class, $produceItemModel->id, WarehouseReceivingModel::class, $warehouseReceiving->id, $warehouseReceiving->getAttributes(), $fields['created_by_id'], 1);

                    $productionToBakeAssemble = $productionBatch->productionOta ?? $productionBatch->productionOtb;
                    $productionToBakeAssemble->received_items_count += 1;
                    $productionToBakeAssemble->save();
                    $this->createProductionLog(get_class($productionToBakeAssemble), $productionToBakeAssemble->id, $producedItems[$value['sticker_no']], $fields['created_by_id'], 1, $value['sticker_no']);
                }
                unset($value);
            }
            $primaryConversionUnitExtraction = array_keys($remainingQuantity)[0];
            if ($remainingPieces > 0) {
                $remainingQuantity[$primaryConversionUnitExtraction] .= ".$remainingPieces";
            }
            $warehousePutAway = new WarehousePutAwayModel();
            $warehousePutAway->created_by_id = $fields['created_by_id'];
            $warehousePutAway->reference_number = $referenceNumber;
            $warehousePutAway->warehouse_receiving_reference_number = $warehouseReceivingReferenceNumber;
            $warehousePutAway->item_id = $fields['item_id'];
            $warehousePutAway->production_items = json_encode($productionItems);
            $warehousePutAway->discrepancy_data = json_encode($productionItems);
            $warehousePutAway->received_quantity = json_encode($remainingQuantity);
            $warehousePutAway->remaining_quantity = json_encode($remainingQuantity);
            $warehousePutAway->temporary_storage_id = $fields['temporary_storage_id'] ?? null;
            $warehousePutAway->save();
            $this->createWarehouseLog(null, null, WarehousePutAwayModel::class, $warehousePutAway->id, $warehousePutAway->getAttributes(), $fields['created_by_id'], 0);

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }
    #endregion

    #region Getters
    public function onGetByPutAwayKey($put_away_key)
    {
        try {
            $explodeKey = explode('-', $put_away_key);
            $warehouseReceivingReferenceNumber = $explodeKey[0];
            $itemId = $explodeKey[1];
            $temporaryStorageId = $explodeKey[2] ?? null;

            $warehousePutAwayModel = WarehousePutAwayModel::select(
                '*',
                DB::raw('JSON_LENGTH(discrepancy_data) as discrepancy_quantity') // discrepancy_data_count
            )
                ->with(['itemMasterdata', 'subLocation'])
                ->where([
                    'warehouse_receiving_reference_number' => $warehouseReceivingReferenceNumber,
                    'item_id' => $itemId
                ]);
            if ($temporaryStorageId != null) {
                $warehousePutAwayModel->where('temporary_storage_id', $temporaryStorageId);
            }
            $warehousePutAwayModel = $warehousePutAwayModel->get();
            if (count($warehousePutAwayModel) <= 0) {
                throw new Exception('Record not found');
            }

            $putAwayMergedItems = [
                'received_quantity' => 0,
                'transferred_quantity' => 0,
                'substandard_quantity' => 0,
                'discrepancy_quantity' => 0,
                'production_items' => []
            ];
            foreach ($warehousePutAwayModel as $warehousePutAway) {
                $productionItems = json_decode($warehousePutAway->production_items, true);
                foreach ($productionItems as $key => &$item) {
                    if ($item['status'] != '3') {
                        unset($productionItems[$key]);
                    }
                }
                if (!array_key_exists('warehouse_put_away_details', $putAwayMergedItems)) {
                    $checkHasForPutAway = WarehouseForPutAwayV2Model::select('id')->where([
                        'warehouse_put_away_key' => $put_away_key
                    ])->first();

                    $itemMasterdata = $warehousePutAway->itemMasterdata;
                    $putAwayMergedItems['warehouse_put_away_details'] = [
                        'warehouse_receiving_reference_number' => $warehousePutAway->warehouse_receiving_reference_number,
                        'item_code' => $itemMasterdata->item_code,
                        'item_description' => $itemMasterdata->description,
                        'item_uom' => $itemMasterdata->uom->long_name,
                        'item_primary_conversion_unit' => $itemMasterdata->primaryConversion->long_name ?? null,
                        'actual_storage_type' => [
                            'storage_type_id' => $itemMasterdata->actual_storage_type_id,
                            'storage_type' => $itemMasterdata->actualStorageType->long_name ?? null
                        ],
                        'sub_location_code' => $warehousePutAway->subLocation->code,
                    ];
                    if ($checkHasForPutAway) {
                        $putAwayMergedItems['warehouse_put_away_details']['warehouse_for_put_away_v2_id'] = $checkHasForPutAway->id;
                    }
                }
                $receivedQuantity = array_values(json_decode($warehousePutAway->received_quantity ?? '[]', true))[0] ?? 0;
                $transferredQuantity = array_values(json_decode($warehousePutAway->transferred_quantity ?? '[]', true))[0] ?? 0;
                $substandardQuantity = array_values(json_decode($warehousePutAway->substandard_quantity ?? '[]', true))[0] ?? 0;
                $discrepancyQuantity = count(json_decode($warehousePutAway->discrepancy_data ?? '[]')) ?? 0;

                $putAwayMergedItems['production_items'] = array_merge($putAwayMergedItems['production_items'], array_values($productionItems));
                $putAwayMergedItems['received_quantity'] += $receivedQuantity;
                $putAwayMergedItems['transferred_quantity'] += $transferredQuantity;
                $putAwayMergedItems['substandard_quantity'] += $substandardQuantity;
                $putAwayMergedItems['discrepancy_quantity'] += $discrepancyQuantity;
            }
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.record_found'), $putAwayMergedItems);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Warehouse Put Away ' . __('msg.record_not_found'));
        }
    }
    public function onGetCurrent($status, $filter = null)
    {
        // put date filtering
        $warehousePutAwayModel = WarehousePutAwayModel::select(
            '*',
            DB::raw('JSON_LENGTH(discrepancy_data) as discrepancy_quantity') // discrepancy_data_count
        )
            ->where('status', $status);

        if ($status != 0) {
            $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);
            if ($whereObject) {
                $warehousePutAwayModel->whereDate('completed_at', $filter);
            } else {
                $yesterday = (new \DateTime('yesterday'))->format('Y-m-d 00:00:00');
                $today = (new \DateTime('today'))->format('Y-m-d 23:59:59');
                $warehousePutAwayModel->whereBetween('completed_at', [$yesterday, $today]);
            }
        }
        $warehousePutAwayModel->orderBy('id', 'ASC');
        $warehousePutAwayModel = $warehousePutAwayModel->get();

        $warehousePutAwayMerged = []; // Warehouse Receiving Reference Number - Item Id - Temporary Storage ID
        foreach ($warehousePutAwayModel as $warehousePutAway) {
            $itemMasterdata = $warehousePutAway->itemMasterdata;
            $itemCode = $itemMasterdata->item_code;
            $itemId = $itemMasterdata->id;

            $warehouseReferenceNumber = $warehousePutAway->warehouse_receiving_reference_number;
            $temporaryStorageId = $warehousePutAway->temporary_storage_id ?? 'Nan';
            $putAwayKey = "$warehouseReferenceNumber-$itemId-$temporaryStorageId";

            // Whole Items
            $receivedQuantity = array_values(json_decode($warehousePutAway->received_quantity ?? '[]', true))[0] ?? 0;
            $transferredQuantity = array_values(json_decode($warehousePutAway->transferred_quantity ?? '[]', true))[0] ?? 0;
            $discrepancyQuantity = count(json_decode($warehousePutAway->discrepancy_data ?? '[]')) ?? 0;

            if (!array_key_exists($putAwayKey, $warehousePutAwayMerged)) {
                $warehousePutAwayMerged[$putAwayKey] = [
                    'item_code' => $itemCode,
                    'warehouse_receiving_reference_number' => $warehouseReferenceNumber,
                    'temporary_storage_id' => $temporaryStorageId,
                    'sub_location_code' => $warehousePutAway->subLocation->code ?? null,
                    'received_quantity' => $receivedQuantity,
                    'transferred_quantity' => $transferredQuantity,
                    'discrepancy_quantity' => $discrepancyQuantity,
                ];
            } else {
                $warehousePutAwayMerged[$putAwayKey]['received_quantity'] += $receivedQuantity;
                $warehousePutAwayMerged[$putAwayKey]['transferred_quantity'] += $transferredQuantity;
                $warehousePutAwayMerged[$putAwayKey]['discrepancy_quantity'] += $discrepancyQuantity;
            }
        }
        return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.create_success'), $warehousePutAwayMerged);
    }
    public function onCompleteTransaction(Request $request, $put_away_key)
    {
        $fields = $request->validate([
            'created_by_id' => 'required'
        ]);
        try {
            $createdById = $fields['created_by_id'];
            $explodeKey = explode('-', $put_away_key);
            $warehouseReceivingReferenceNumber = $explodeKey[0];
            $itemId = $explodeKey[1];
            $temporaryStorageId = $explodeKey[2] ?? null;
            $warehousePutAway = WarehousePutAwayModel::where([
                'warehouse_receiving_reference_number' => $warehouseReceivingReferenceNumber,
                'item_id' => $itemId,
                'status' => 0,
            ]);
            if ($temporaryStorageId != null) {
                $warehousePutAway->where('temporary_storage_id', $temporaryStorageId);
            }
            $warehousePutAway = $warehousePutAway->firstOrFail();
            DB::beginTransaction();

            if ($warehousePutAway) {
                $temporaryStorageId = $warehousePutAway->temporary_storage_id ?? null;
                $warehousePutAway->status = 1;
                $warehousePutAway->temporary_storage_id = null;
                $warehousePutAway->completed_at = now();
                $warehousePutAway->save();
                $this->createWarehouseLog(null, null, WarehousePutAwayModel::class, $warehousePutAway->id, $warehousePutAway->getAttributes(), $createdById, 0);

                if ($temporaryStorageId != null) {
                    QueuedTemporaryStorageModel::where('sub_location_id', $temporaryStorageId)->delete();
                }

                /* Dont know if this will work in the new code, revert if necessary
                $parentReferenceNumber = explode('-', $put_away_reference_number)[0];
                $warehousePutAwayCount = WarehousePutAwayModel::select([
                    DB::raw('COUNT(id) as total_count'),
                    DB::raw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as complete_count')
                ])->where('warehouse_receiving_reference_number', $parentReferenceNumber)
                    ->first();

                if ($warehousePutAwayCount->complete_count == $warehousePutAwayCount->total_count) {
                    QueuedTemporaryStorageModel::where('sub_location_id', $temporaryStorageId)->delete();
                }
                */

            }
            DB::commit();
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Warehouse Put Away ' . __('msg.update_failed'), $exception->getMessage());
        }
    }
    #endregion

    #region Old version
    public function onSubStandard(Request $request, $warehouse_put_away_id)
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
            $warehousePutAwayId = $warehouse_put_away_id;
            $itemCode = null;

            // Checking of Scanned For Transfer items, will be removed when it is also scanned for substandard
            $matchedScannedItemSubstandard = [];
            $warehouseForPutAwayModel = WarehouseForPutAwayModel::where('warehouse_put_away_id', $warehouse_put_away_id)->first();
            if ($warehouseForPutAwayModel) {
                $transferItems = json_decode($warehouseForPutAwayModel->transfer_items, true);
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
                $productionItems = json_decode($warehouseForPutAwayModel->production_items, true);
                $filteredProductionItemForPutAway = array_filter($productionItems, function ($item1) use ($matchedScannedItemSubstandard) {
                    foreach ($matchedScannedItemSubstandard as $item2) {
                        if ($item1['bid'] == $item2['bid'] && $item1['sticker_no'] == $item2['sticker_no']) {
                            return false;
                        }
                    }
                    return true;
                });
                $warehouseForPutAwayModel->production_items = json_encode(array_values($filteredProductionItemForPutAway));
                $warehouseForPutAwayModel->transfer_items = json_encode(array_values($transferItems));
                $warehouseForPutAwayModel->save();
            }

            foreach ($scannedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionItem = $productionBatch->productionItems;
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $itemId = $productionOrderToMake->itemMasterdata->id;
                $inclusionArray = ['3.1', '3'];
                $itemMasterdata = $productionOrderToMake->itemMasterdata;
                $primaryUom = $itemMasterdata->uom->long_name ?? null;
                $primaryConversion = $itemMasterdata->primaryConversion->long_name ?? null;
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                if ($flag) {
                    $warehousePutAway = WarehousePutAwayModel::where('id', $warehouse_put_away_id)
                        ->where('item_id', $itemId)
                        ->first();
                    if ($warehousePutAway) {
                        $discrepancyDataPutAway = json_decode($warehousePutAway->discrepancy_data, true);
                        $warehousePutAwayProducedItems = json_decode($warehousePutAway->production_items, true);
                        $stickerNumber = array_column($warehousePutAwayProducedItems, 'sticker_no');
                        $stickerIndex = array_search($itemDetails['sticker_no'], $stickerNumber);
                        $warehousePutAwayProducedItems[$stickerIndex]['status'] = 1.1;
                        $warehousePutAway->production_items = json_encode($warehousePutAwayProducedItems);
                        $substandardQuantity = json_decode($warehousePutAway->substandard_quantity, true);
                        $remainingQuantity = json_decode($warehousePutAway->remaining_quantity, true);
                        if ($primaryUom) {
                            if (!isset($remainingQuantity[$primaryUom])) {
                                $remainingQuantity[$primaryUom] = 0;
                            }
                            if (!isset($substandardQuantity[$primaryUom])) {
                                $substandardQuantity[$primaryUom] = 0;
                            }
                            $remainingQuantity[$primaryUom]--;
                            $substandardQuantity[$primaryUom]++;
                        }

                        if ($primaryConversion) {
                            if (!isset($remainingQuantity[$primaryConversion])) {
                                $remainingQuantity[$primaryConversion] = 0;
                            }
                            if (!isset($substandardQuantity[$primaryConversion])) {
                                $substandardQuantity[$primaryConversion] = 0;
                            }
                            $remainingQuantity[$primaryConversion] -= intval($itemDetails['q']);
                            $substandardQuantity[$primaryConversion] += intval($itemDetails['q']);
                        }

                        foreach ($discrepancyDataPutAway as $key => &$item) {
                            if ($item['bid'] == $itemDetails['bid'] && $item['sticker_no'] == $itemDetails['sticker_no']) {
                                unset($discrepancyDataPutAway[$key]);
                                break;
                            }
                        }

                        $warehousePutAway->remaining_quantity = json_encode($remainingQuantity);
                        $warehousePutAway->substandard_quantity = json_encode($substandardQuantity);
                        $warehousePutAway->discrepancy_data = json_encode(array_values($discrepancyDataPutAway));
                        $warehousePutAway->save();

                    }
                }
            }
            $substandardController = new SubStandardItemController();
            $substandardRequest = new Request([
                'created_by_id' => $createdById,
                'scanned_items' => $fields['scanned_items'],
                'reason' => $reason,
                'attachment' => $attachment,
                'location_id' => $locationId,
            ]);

            $substandardController->onCreate($substandardRequest);

            $queueSubLocationController = new QueuedSubLocationController();
            $queueSubLocationRequest = new Request([
                'created_by_id' => $createdById,
                'warehouse_put_away_id' => $warehousePutAwayId,
                'item_id' => $itemId,
            ]);
            $queueSubLocationController->onCreate($queueSubLocationRequest);
            DB::commit();
            return $this->dataResponse('success', 201, 'Warehouse Put Away ' . __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Warehouse Put Away ' . __('msg.create_failed'));
        }
    }

    public function onCheckItemReceive($receiveItemsArr, $key, $value, $ReferenceItemCode)
    {
        try {
            foreach ($receiveItemsArr as $receiveValue) {
                if (($receiveValue['bid'] == $value['bid']) && ($receiveValue['sticker_no'] == $key) && ($receiveValue['item_code'] == $ReferenceItemCode)) {
                    return true;
                }
            }
            return false;
        } catch (Exception $exception) {
            return false;
        }
    }

    #region Check Item Status
    public function onItemCheckHoldInactiveDone($producedItems, $itemKey, $inclusionArray, $exclusionArray)
    {
        $inArrayFlag = count($inclusionArray) > 0 ?
            in_array($producedItems[$itemKey]['status'], $inclusionArray) :
            !in_array($producedItems[$itemKey]['status'], $exclusionArray);
        return $producedItems[$itemKey]['sticker_status'] != 0 && $inArrayFlag;
    }

    public function onCheckScannedItems($scannedItems, $stickerNumber, $batchId)
    {
        try {
            foreach ($scannedItems as $value) {
                if (($value['sticker_no'] == $stickerNumber) && $value['bid'] == $batchId) {
                    return true;
                }
            }

            return false;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());

        }
    }
    #endregion
}

