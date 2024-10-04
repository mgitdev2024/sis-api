<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\QualityAssurance\SubStandardItemController;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\MOS\Production\ProductionOrderModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
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
            $isDate = \DateTime::createFromFormat('Y-m-d', $filter);

            $warehouseReceivingModel = WarehouseReceivingModel::select(
                'reference_number',
                'temporary_storage_id',
                DB::raw('MAX(created_at) as latest_created_at'),
                DB::raw('count(*) as batch_count'),
                DB::raw('SUM(substandard_quantity) as substandard_quantity'),
                DB::raw('SUM(received_quantity) as received_quantity'),
                DB::raw('SUM(JSON_LENGTH(produced_items))  as produced_items_count'),
                DB::raw('SUM(JSON_LENGTH(discrepancy_data))  as discrepancy_data_count') // discrepancy_data_count
            )
                ->where('status', $status);
            if ($filter != null) {
                $warehouseReceivingModel->where('production_order_id', $filter);
            } else {
                $today = new \DateTime('today');
                $tomorrow = new \DateTime('tomorrow');
                $productionOrderModel = ProductionOrderModel::whereBetween('production_date', [$today->format('Y-m-d'), $tomorrow->format('Y-m-d')])->pluck('id');
                $warehouseReceivingModel->whereIn('production_order_id', $productionOrderModel);
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
                DB::raw('JSON_ARRAYAGG(produced_items) as aggregated_produced_items')
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
                    'item_code' => $itemCode,
                    'item_id' => $itemMasterdataModel->id,
                    'sku_type' => $itemMasterdataModel->item_category_label,
                    'produced_items' => $producedItemsQuantity <= 0 ? 1 : $producedItemsQuantity,
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
            'scanned_items' => 'nullable|string', // {slid:1}
            'action' => 'required|string|in:0,1', // 0 = Scan, 1 = Complete Transaction
        ]);
        try {
            DB::beginTransaction();
            if ($fields['action'] == 0) {
                $scannedItems = json_decode($fields['scanned_items'], true);
                $this->onScanItems($scannedItems, $referenceNumber, $fields['created_by_id']);
                $this->onCreatePutAway($scannedItems, $referenceNumber, $fields['created_by_id']);

            } else {
                $this->onCompleteTransaction($referenceNumber, $fields['created_by_id']);
            }

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Warehouse Receiving ' . $exception->getMessage());
        }
    }

    public function onScanItems($scannedItems, $referenceNumber, $createdById)
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
                        $warehouseForReceive = WarehouseForReceiveModel::where('reference_number', $referenceNumber)->update(['status' => 0]);
                        $warehouseProducedItems = json_decode($warehouseReceiving->produced_items, true);
                        $warehouseProducedItems[$itemDetails['sticker_no']]['status'] = '2.1';
                        $warehouseReceiving->produced_items = json_encode($warehouseProducedItems);
                        $warehouseReceiving->received_quantity = ++$warehouseReceiving->received_quantity;
                        $warehouseReceiving->updated_by_id = $createdById;
                        $warehouseReceiving->save();
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
            $warehouseForReceiveItems = WarehouseForReceiveModel::where('reference_number', $referenceNumber)
                ->where('created_by_id', $createdById)
                ->orderBy('id', 'DESC')
                ->first();
            $receiveItemsArr = $warehouseForReceiveItems ? (json_decode($warehouseForReceiveItems->production_items, true) ?? []) : [];
            // if (count($receiveItemsArr) <= 0) {
            //     throw new Exception('There are no items to be received from this repository');
            // }
            DB::beginTransaction();
            foreach ($warehouseReceiving as &$warehouseReceivingValue) {
                $warehouseReceivingCurrentItemCode = $warehouseReceivingValue['item_code'];
                $referenceItemId = ItemMasterdataModel::where('item_code', $warehouseReceivingCurrentItemCode)->first()->id;
                $warehouseProducedItems = json_decode($warehouseReceivingValue['produced_items'], true);
                $productionItemModel = $warehouseReceivingValue->productionBatch->productionItems;
                // $producedItems = json_decode($productionItemModel->produced_items, true);

                $discrepancy = [];
                foreach ($warehouseProducedItems as $innerWarehouseReceivingKey => &$innerWarehouseReceivingValue) {
                    $flag = $this->onCheckItemReceive($receiveItemsArr, $innerWarehouseReceivingKey, $innerWarehouseReceivingValue, $referenceItemId);
                    if (!$flag) {
                        $innerWarehouseReceivingValue['sticker_no'] = $innerWarehouseReceivingKey;
                        $discrepancy[] = $innerWarehouseReceivingValue;
                    }
                    unset($innerWarehouseReceivingValue);
                }
                $warehouseForReceive = WarehouseForReceiveModel::where('reference_number', $referenceNumber)->delete();
                $warehouseReceivingValue->status = 1;
                $warehouseReceivingValue->updated_by_id = $createdById;
                $warehouseReceivingValue->discrepancy_data = count($discrepancy) > 0 ? json_encode($discrepancy) : null;
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
            $scannedItems = json_decode($fields['scanned_items'], true);
            $reason = $fields['reason'];
            $attachment = $fields['attachment'] ?? null;
            $locationId = 3; // Warehouse Receiving
            foreach ($scannedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionItem = $productionBatch->productionItems;
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $inclusionArray = [2];
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                if ($flag) {
                    $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $referenceNumber)
                        ->where('production_order_id', $productionBatch->production_order_id)
                        ->where('batch_number', $productionBatch->batch_number)
                        ->where('item_code', $itemCode)
                        ->first();
                    if ($warehouseReceiving) {
                        $warehouseReceivingProducedItems = json_decode($warehouseReceiving->produced_items, true);
                        $warehouseReceivingProducedItems[$itemDetails['sticker_no']]['status'] = 1.1;
                        $warehouseReceiving->produced_items = json_encode($warehouseReceivingProducedItems);
                        $warehouseReceiving->substandard_quantity = ++$warehouseReceiving->substandard_quantity;
                        $warehouseReceiving->save();
                        $this->createWarehouseLog(null, null, WarehouseReceivingModel::class, $warehouseReceiving->id, $warehouseReceiving->getAttributes(), $createdById, 1);

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
