<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\QualityAssurance\SubStandardItemController;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use App\Models\WMS\Warehouse\WarehouseBulkReceivingModel;
use App\Models\WMS\Warehouse\WarehouseForReceiveModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use DB;
use Exception;
use App\Traits\MOS\MosCrudOperationsTrait;

class WarehouseReceivingController extends Controller
{
    use MosCrudOperationsTrait, QueueSubLocationTrait;
    public function onGetAllCategory($status, $filter = null)
    {
        try {
            $warehouseReceivingModel = WarehouseReceivingModel::select(
                'reference_number',
                'temporary_storage_id',
                DB::raw('MAX(created_at) as latest_created_at'),
                DB::raw('MAX(completed_at) as latest_completed_at'),
                DB::raw('count(*) as batch_count'),
                DB::raw('SUM(substandard_quantity) as substandard_quantity'),
                DB::raw('SUM(received_quantity) as received_quantity'),
                DB::raw('SUM(JSON_LENGTH(produced_items)) as produced_items_count'),
                DB::raw('SUM(JSON_LENGTH(discrepancy_data)) as discrepancy_data_count') // discrepancy_data_count
            )
                ->where('status', $status);
            if ($status != 0) {
                $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);
                if ($whereObject) {
                    $warehouseReceivingModel->whereDate('completed_at', $filter);
                } else {
                    $yesterday = (new \DateTime('yesterday'))->format('Y-m-d 00:00:00');
                    $today = (new \DateTime('today'))->format('Y-m-d 23:59:59');
                    $warehouseReceivingModel->whereBetween('completed_at', [$yesterday, $today]);
                }
            }

            $warehouseReceivingModel = $warehouseReceivingModel->groupBy([
                'reference_number',
                'temporary_storage_id'
            ])
                ->orderBy('latest_created_at', 'DESC')
                ->get();

            $warehouseReceiving = [];
            $counter = 0;
            foreach ($warehouseReceivingModel as $value) {
                $warehouseReceiving[$counter] = [
                    'reference_number' => $value->reference_number,
                    'temporary_storage' => SubLocationModel::find($value->temporary_storage_id)->code ?? 'N/A',
                    'transaction_date' => date('Y-m-d (h:i:A)', strtotime($value->latest_created_at)) ?? null,
                    'completed_at_date' => date('Y-m-d (h:i:A)', strtotime($value->latest_completed_at)) ?? null,
                    'batch_count' => $value->batch_count,
                    'quantity' => $value->produced_items_count,
                    'received_quantity' => $value->received_quantity,
                    'substandard_quantity' => $value->substandard_quantity,
                    'discrepancy_quantity' => $value->discrepancy_data_count ?? 0,
                ];
                ++$counter;
            }
            if (count($warehouseReceiving) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $warehouseReceiving);
            }
            return $this->dataResponse('error', 200, 'Warehouse Receiving ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetCurrent($referenceNumber, $status, $received_status = null)
    {
        try {
            $warehouseReceivingAdd = WarehouseReceivingModel::select(
                'reference_number',
                'item_code',
                DB::raw('SUM(substandard_quantity) as substandard_quantity'),
                DB::raw('SUM(received_quantity) as received_quantity'),
                DB::raw('SUM(JSON_LENGTH(produced_items)) as produced_items_count'),
                DB::raw('JSON_ARRAYAGG(produced_items) as aggregated_produced_items'),
                DB::raw('SUM(JSON_LENGTH(discrepancy_data)) as discrepancy_data_count')
            )
                ->where('status', $status)
                ->where('reference_number', $referenceNumber)
                ->groupBy([
                    'item_code',
                    'reference_number'
                ]);
            if ($received_status == 1) {
                $warehouseReceivingAdd->havingRaw('SUM(received_quantity) + SUM(substandard_quantity) <> SUM(JSON_LENGTH(produced_items))');
            }

            $warehouseReceiving = $warehouseReceivingAdd->get();

            $isCompleteWarehouseReceive = WarehouseReceivingModel::select(
                'reference_number',
                DB::raw('(SUM(substandard_quantity) + SUM(received_quantity) = SUM(JSON_LENGTH(produced_items))) as is_completed')
            )
                ->where('reference_number', $referenceNumber)
                ->groupBy('reference_number')
                ->first();

            $isCompleted = $isCompleteWarehouseReceive->is_completed;

            $warehouseReceivingArr = [
                'is_reference_complete' => $isCompleted,
                'warehouse_receiving_items' => []
            ];
            foreach ($warehouseReceiving as $value) {
                $itemCode = $value->item_code;

                // aggregated produced items decoding
                $producedItemsQuantity = 0;
                foreach (json_decode($value->aggregated_produced_items, true) as $aggregatedValue) {
                    foreach (json_decode($aggregatedValue, true) as $producedItemValue) {
                        $producedItemsQuantity += $producedItemValue['q'];
                    }
                }
                $itemMasterdataModel = ItemMasterdataModel::where('item_code', $itemCode)->first();
                $warehouseReceivingArr['warehouse_receiving_items'][] = [
                    'reference_number' => $value->reference_number,
                    'quantity' => $value->produced_items_count,
                    'received_quantity' => $value->received_quantity,
                    'substandard_quantity' => $value->substandard_quantity,
                    'discrepancy_quantity' => $value->discrepancy_data_count ?? 0,
                    'item_code' => $itemCode,
                    'item_id' => $itemMasterdataModel->id,
                    'sku_type' => $itemMasterdataModel->item_category_label,
                    'produced_items_quantity' => $producedItemsQuantity <= 0 ? 1 : $producedItemsQuantity,
                ];
            }
            if (count($warehouseReceivingArr) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $warehouseReceivingArr);
            }
            return $this->dataResponse('error', 200, 'Warehouse Receiving ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetById($id)
    {
        return $this->readRecordById(WarehouseReceivingModel::class, $id, 'Warehouse Receiving');
    }
    public function onUpdate(Request $request, $referenceNumber)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'action' => 'required|string|in:0,1', // 0 = Scan, 1 = Complete Transaction
            'bulk_data' => 'nullable|json'
        ]);
        try {
            DB::beginTransaction();
            if ($fields['action'] == 0) {
                $scannedItems = null;
                $isBulk = false;
                if (isset($fields['bulk_data'])) {
                    $scannedItems = json_decode($fields['bulk_data'], true);
                    $isBulk = true;
                } else {
                    $warehouseForReceiveModel = WarehouseForReceiveModel::where([
                        'reference_number' => $referenceNumber,
                        'created_by_id' => $fields['created_by_id']
                    ])
                        ->orderBy('id', 'DESC')
                        ->first();
                    $scannedItems = json_decode($warehouseForReceiveModel->production_items, true);
                }
                $this->onScanItems($scannedItems, $referenceNumber, $fields['created_by_id'], $isBulk);
                $this->onCreatePutAway($scannedItems, $referenceNumber, $fields['created_by_id']);

            } else {
                $this->onCompleteTransaction($referenceNumber, $fields['created_by_id']);
            }

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollback();
            dd($exception);
            return $this->dataResponse('error', 400, 'Warehouse Receiving ' . $exception->getMessage());
        }
    }

    public function onScanItems($scannedItems, $referenceNumber, $createdById, $isBulk)
    {
        try {
            DB::beginTransaction();
            foreach ($scannedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionItem = $productionBatch->productionItems;
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $inclusionArray = [2];
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                if ($flag) {
                    $producedItems = json_decode($productionItem->produced_items, true);
                    $producedItems[$itemDetails['sticker_no']]['status'] = '2.1';
                    $productionItem->produced_items = json_encode($producedItems);
                    $productionItem->save();
                    $this->createProductionLog(ProductionItemModel::class, $productionItem->id, $producedItems[$itemDetails['sticker_no']], $createdById, 1, $itemDetails['sticker_no']);

                    $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $referenceNumber)
                        ->where('production_order_id', $productionBatch->production_order_id)
                        ->where('batch_number', $productionBatch->batch_number)
                        ->where('item_code', $itemCode)
                        ->first();

                    if ($warehouseReceiving) {
                        $discrepancyData = json_decode($warehouseReceiving->discrepancy_data, true) ?? [];
                        if (isset($discrepancyData[$itemDetails['sticker_no']])) {
                            unset($discrepancyData[$itemDetails['sticker_no']]);
                        }

                        $warehouseProducedItems = json_decode($warehouseReceiving->produced_items, true);
                        $warehouseProducedItems[$itemDetails['sticker_no']]['status'] = '2.1';
                        $warehouseReceiving->produced_items = json_encode($warehouseProducedItems);
                        $warehouseReceiving->received_quantity = ++$warehouseReceiving->received_quantity;
                        $warehouseReceiving->discrepancy_data = json_encode($discrepancyData);
                        $warehouseReceiving->updated_by_id = $createdById;
                        $warehouseReceiving->save();

                        if (!$isBulk) {
                            WarehouseForReceiveModel::where('reference_number', $referenceNumber)->delete();
                        }
                        $this->createWarehouseLog(ProductionItemModel::class, $productionItem->id, WarehouseReceivingModel::class, $warehouseReceiving->id, $warehouseReceiving->getAttributes(), $createdById, 1);
                    }
                }
            }
            DB::commit();

        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }

    public function onCompleteTransaction($referenceNumber, $createdById)
    {
        try {
            $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $referenceNumber)
                ->where('status', 0)
                ->get();

            if (count($warehouseReceiving) <= 0) {
                throw new Exception('Reference number already received');
            }

            DB::beginTransaction();
            foreach ($warehouseReceiving as &$warehouseReceivingValue) {
                $productionItemModel = $warehouseReceivingValue->productionBatch->productionItems;
                $warehouseReceivingValue->status = 1;
                $warehouseReceivingValue->updated_by_id = $createdById;
                $warehouseReceivingValue->completed_at = now();
                $warehouseReceivingValue->save();
                $this->createWarehouseLog(ProductionItemModel::class, $productionItemModel->id, WarehouseReceivingModel::class, $warehouseReceivingValue->id, $warehouseReceivingValue->getAttributes(), $createdById, 1);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }

    public function onCheckItemReceive($receiveItemsArr, $key, $value, $referenceItemId)
    {
        try {
            foreach ($receiveItemsArr as $receiveValue) {
                if (($receiveValue['bid'] == $value['bid']) && ($receiveValue['sticker_no'] == $key) && ($receiveValue['item_id'] == $referenceItemId)) {
                    return true;
                }
            }
            return false;
        } catch (Exception $exception) {

            return false;
        }
    }

    public function onItemCheckHoldInactiveDone($producedItems, $itemKey, $inclusionArray, $exclusionArray)
    {
        $inArrayFlag = count($inclusionArray) > 0 ?
            in_array($producedItems[$itemKey]['status'], $inclusionArray) :
            !in_array($producedItems[$itemKey]['status'], $exclusionArray);
        return $producedItems[$itemKey]['sticker_status'] != 0 && $inArrayFlag;
    }

    public function onSubStandard(Request $request, $referenceNumber)
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
            $substandardScannedItems = json_decode($fields['scanned_items'], true);
            $reason = $fields['reason'];
            $attachment = $fields['attachment'] ?? null;
            $locationId = 3; // Warehouse Receiving

            $warehouseForReceiveItems = WarehouseForReceiveModel::where('reference_number', $referenceNumber)
                ->where('created_by_id', $createdById)
                ->orderBy('id', 'DESC')
                ->first();
            $toBeLogged = [];
            foreach ($substandardScannedItems as $substandardItem) {
                $productionBatchModel = ProductionBatchModel::find($substandardItem['bid']);
                $batchNumber = $productionBatchModel->batch_number;
                $productionOrderId = $productionBatchModel->production_order_id;
                $itemCode = $productionBatchModel->item_code;
                $productionItemModel = $productionBatchModel->productionItems;
                $inclusionArray = [2];
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItemModel->produced_items, true), $substandardItem['sticker_no'], $inclusionArray, []);
                if ($flag) {
                    $warehouseReceivingModel = WarehouseReceivingModel::where([
                        'item_code' => $itemCode,
                        'batch_number' => $batchNumber,
                        'production_order_id' => $productionOrderId,
                        'reference_number' => $referenceNumber
                    ])->first();

                    if ($warehouseReceivingModel) {
                        $warehouseReceivingProductionItems = json_decode($warehouseReceivingModel->produced_items, true);
                        $discrepancyData = json_decode($warehouseReceivingModel->discrepancy_data, true) ?? [];
                        if (isset($discrepancyData[$substandardItem['sticker_no']])) {
                            unset($discrepancyData[$substandardItem['sticker_no']]);
                        }
                        $warehouseReceivingSubstandardData = json_decode($warehouseReceivingModel->substandard_data, true) ?? [];
                        $mergedItems = array_merge($warehouseReceivingSubstandardData, $substandardItem);
                        $warehouseReceivingProductionItems[$substandardItem['sticker_no']]['status'] = 1.1;

                        // Saving to db
                        $warehouseReceivingModel->substandard_quantity += 1;
                        $warehouseReceivingModel->substandard_data = json_encode($mergedItems);
                        $warehouseReceivingModel->discrepancy_data = json_encode($discrepancyData);
                        $warehouseReceivingModel->produced_items = json_encode($warehouseReceivingProductionItems);
                        $warehouseReceivingModel->save();

                        $warehouseReceivingLogKey = "$warehouseReceivingModel->item_code-$warehouseReceivingModel->batch_number-$warehouseReceivingModel->production_order_id-$warehouseReceivingModel->reference_number";

                        $toBeLogged[$warehouseReceivingLogKey] = $warehouseReceivingModel;

                    }
                }
            }

            if (count($toBeLogged) > 0) {
                foreach ($toBeLogged as $logs) {
                    $this->createWarehouseLog(null, null, WarehouseReceivingModel::class, $logs->id, $logs->getAttributes(), $createdById, 1);
                }
            }

            if ($warehouseForReceiveItems) {
                $warehouseForReceiveItems->status = 0;
                $warehouseForReceiveItems->save();
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

            $warehouseReceivingController = new WarehouseReceivingController();
            $createPutAwayRequest = new Request([
                'created_by_id' => $createdById,
                'action' => '0'
            ]);

            $warehouseReceivingController->onUpdate($createPutAwayRequest, $referenceNumber);
            DB::commit();
            return $this->dataResponse('success', 201, 'Sub-Standard ' . __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Sub-Standard ' . __('msg.create_failed'));
        }
    }

    public function onCreatePutAway($scannedItems, $referenceNumber, $createdById)
    {
        try {
            $itemCodeArr = [];
            $subLocationId = null;
            foreach ($scannedItems as $value) {
                $productionBatch = ProductionBatchModel::find($value['bid']);
                $itemCode = $productionBatch->item_code;
                if (!in_array($itemCode, $itemCodeArr)) {
                    $itemCodeArr[] = $itemCode;
                }
                $productionItem = json_decode($productionBatch->productionItems->produced_items, true);
                $subLocationId = $productionItem[$value['sticker_no']]['sub_location']['sub_location_id'] ?? null;
            }
            $warehouseReceivingArr = [];
            foreach ($itemCodeArr as $itemCode) {
                $warehouseReceivingModel = WarehouseReceivingModel::select([
                    'reference_number',
                    'item_code',
                    DB::raw('SUM(received_quantity) as received_quantity'),
                ])
                    ->where('reference_number', $referenceNumber)
                    ->where('item_code', $itemCode)
                    ->groupBy([
                        'reference_number',
                        'item_code'
                    ])
                    ->first();
                if (!array_key_exists($itemCode, $warehouseReceivingArr)) {
                    $warehouseReceivingArr[$itemCode] = $warehouseReceivingModel->getAttributes();
                }
            }

            foreach ($warehouseReceivingArr as $warehouseReceiving) {
                $warehouseReceivingModel = WarehouseReceivingModel::where('reference_number', $warehouseReceiving['reference_number'])
                    ->where('item_code', $warehouseReceiving['item_code'])
                    ->pluck('produced_items');
                $mergedProducedItemsContainer = [];
                foreach ($warehouseReceivingModel as $productionItems) {
                    $items = json_decode($productionItems, true);
                    $filteredItems = array_filter($items, function ($item) {
                        return isset($item['status']) && $item['status'] == '2.1';
                    });
                    $mergedProducedItemsContainer = array_merge($mergedProducedItemsContainer, $filteredItems);
                }

                $warehousePutAwayController = new WarehousePutAwayController();
                $warehousePutAwayRequest = new Request([
                    'created_by_id' => $createdById,
                    'warehouse_receiving_reference_number' => $warehouseReceiving['reference_number'],
                    'received_quantity' => $warehouseReceiving['received_quantity'],
                    'production_items' => json_encode($mergedProducedItemsContainer),
                    'item_id' => ItemMasterdataModel::where('item_code', $warehouseReceiving['item_code'])->first()->id,
                    'scanned_items' => json_encode($scannedItems),
                    'temporary_storage_id' => $subLocationId
                ]);
                $warehousePutAwayController->onCreate($warehousePutAwayRequest);
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }

    public function onCompleteTransactionMVP(Request $request, $reference_number)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            $itemsInQueue = [];
            $warehouseReceiving = WarehouseReceivingModel::where([
                'reference_number' => $reference_number,
                'status' => 0
            ])->get();
            if (count($warehouseReceiving) <= 0) {
                return $this->dataResponse('error', 400, 'Warehouse Receiving ' . __('msg.record_not_found'));
            }
            foreach ($warehouseReceiving as $warehouseReceivingValue) {
                $warehouseReceivingProducedItems = json_decode($warehouseReceivingValue->produced_items, true);
                $productionItemModel = $warehouseReceivingValue->productionBatch->productionItems;
                $producedItems = json_decode($productionItemModel->produced_items, true);
                foreach ($warehouseReceivingProducedItems as $stickerNumber => &$itemDetails) {
                    $productionBatch = ProductionBatchModel::find($producedItems[$stickerNumber]['bid']);
                    if ($producedItems[$stickerNumber]['status'] == 2) {
                        $productionToBakeAssemble = $productionBatch->productionOta ?? $productionBatch->productionOtb;
                        $productionToBakeAssemble->received_items_count += 1;
                        $productionToBakeAssemble->save();
                        $this->createProductionLog(get_class($productionToBakeAssemble), $productionToBakeAssemble->id, $producedItems[$stickerNumber], $fields['created_by_id'], 1, $stickerNumber);

                        $itemDetails['status'] = 3;
                        $producedItems[$stickerNumber]['status'] = 3;
                        $itemsInQueue[$stickerNumber] = $producedItems[$stickerNumber];
                        $this->createProductionLog(ProductionItemModel::class, $productionItemModel->id, $producedItems[$stickerNumber], $fields['created_by_id'], 1, $stickerNumber);
                        unset($itemDetails);
                    }
                }
                $productionItemModel->produced_items = json_encode($producedItems);
                $productionItemModel->save();

                // $queueTemporaryStorage
                foreach ($itemsInQueue as $queuedItems) {
                    $subLocationId = $queuedItems['sub_location']['sub_location_id'] ?? null;
                    $queuedTemporaryStorage = QueuedTemporaryStorageModel::where('sub_location_id', $subLocationId)->first();

                    if ($queuedTemporaryStorage) {
                        $queuedTemporaryStorage->delete();
                    }
                }

                $warehouseReceivingValue->status = 1;
                $warehouseReceivingValue->completed_at = now();
                $warehouseReceivingValue->updated_by_id = $fields['created_by_id'];
                $warehouseReceivingValue->produced_items = json_encode($warehouseReceivingProducedItems);
                $warehouseReceivingValue->save();
                $this->createWarehouseLog(ProductionItemModel::class, $productionItemModel->id, WarehouseReceivingModel::class, $warehouseReceivingValue->id, $warehouseReceivingValue->getAttributes(), $fields['created_by_id'], 1);
            }

            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Warehouse Receiving ' . $exception->getMessage());
        }
    }
}
