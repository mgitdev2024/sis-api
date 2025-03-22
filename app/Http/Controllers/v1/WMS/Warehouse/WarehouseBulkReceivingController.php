<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\QualityAssurance\SubStandardItemController;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Warehouse\WarehouseBulkReceivingModel;
use App\Models\WMS\Warehouse\WarehouseForReceiveModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use Exception;
use Illuminate\Http\Request;
use App\Traits\WMS\QueueSubLocationTrait;
use DB;
use Illuminate\Database\QueryException;

class WarehouseBulkReceivingController extends Controller
{
    use QueueSubLocationTrait;

    public function onGetTemporaryStorageItems($sub_location_id, $status)
    {
        try {
            $items = $this->onGetQueuedItems($sub_location_id, false);
            $combinedItems = array_merge(...$items);
            $data = [];
            $currentItemStatus = null;
            foreach ($combinedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $itemId = $productionOrderToMake->itemMasterdata->id;
                $stickerNumber = $itemDetails['sticker_no'];
                $producedItem = json_decode($productionBatch->productionItems->produced_items, true)[$stickerNumber];
                $currentItemStatus = $producedItem['status'];

                if ($producedItem['status'] == $status) {
                    $subLocationId = $producedItem['sub_location']['sub_location_id'];
                    $warehouseReceivingModel = WarehouseReceivingModel::select([
                        'reference_number',
                        'item_code',
                        DB::raw('SUM(received_quantity) as received_quantity'),
                        DB::raw('SUM(JSON_LENGTH(discrepancy_data)) as discrepancy_data')
                    ])
                        ->where([
                            'reference_number' => $producedItem['warehouse']['warehouse_receiving']['reference_number'],
                            'item_code' => $itemCode,
                        ])
                        ->groupBy([
                            'reference_number',
                            'item_code'
                        ])
                        ->first();
                    $data['production_items'][] = [
                        'bid' => $itemDetails['bid'],
                        'item_code' => $itemCode,
                        'item_id' => $itemId,
                        'item_status' => $producedItem['status'],
                        'sticker_no' => $stickerNumber,
                        'q' => $producedItem['q'],
                        'batch_code' => $producedItem['batch_code'],
                        'parent_batch_code' => $producedItem['parent_batch_code'],
                        'slid' => $subLocationId,
                        'rack_code' => SubLocationModel::find($subLocationId)->code,
                        'warehouse' => [
                            'warehouse_receiving' => [
                                'reference_number' => $warehouseReceivingModel->reference_number,
                                'received_quantity' => $warehouseReceivingModel->received_quantity,
                                'to_receive_quantity' => $warehouseReceivingModel->discrepancy_data
                            ]
                        ]
                    ];
                }
            }
            $data['current_item_status'] = $currentItemStatus;
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onCreate(Request $request)
    {
        try {
            $fields = $request->validate([
                'warehouse_production_items' => 'required|json',
                'created_by_id' => 'required',
            ]);
            $warehouseProductionItems = json_decode($fields['warehouse_production_items'], true);
            $createdById = $fields['created_by_id'];
            DB::beginTransaction();

            // delete existing selection
            $this->onRemoveExisting($createdById);
            foreach ($warehouseProductionItems as $warehouseKey => $warehouseItems) {
                $keyExplode = explode('-', $warehouseKey);
                $referenceNumber = $keyExplode[0];
                $subLocationId = $warehouseItems['additional_info']['rack_code'] ?? null
                    ? SubLocationModel::where('code', $warehouseItems['additional_info']['rack_code'])->value('id')
                    : null;

                foreach ($warehouseItems['production_batches'] as $productionBatchId => $productionBatchItems) {
                    $warehouseBulkReceivingModel = new WarehouseBulkReceivingModel();
                    $warehouseBulkReceivingModel->reference_number = $referenceNumber;
                    $warehouseBulkReceivingModel->production_batch_id = $productionBatchId;
                    $warehouseBulkReceivingModel->sub_location_id = $subLocationId;
                    $warehouseBulkReceivingModel->production_items = json_encode($productionBatchItems);
                    $warehouseBulkReceivingModel->created_by_id = $createdById;
                    $warehouseBulkReceivingModel->save();
                }
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onGetAll($created_by_id, $direct_access = false)
    {
        try {
            $warehouseBulkReceivingModel = WarehouseBulkReceivingModel::where('created_by_id', $created_by_id)->get();
            $data = [];

            foreach ($warehouseBulkReceivingModel as $warehouseBulkData) {
                $referenceNumber = $warehouseBulkData->reference_number;
                $productionBatchId = $warehouseBulkData->production_batch_id;
                $productionBatchModel = ProductionBatchModel::find($productionBatchId);
                $itemId = $productionBatchModel->itemMasterdata->id;
                $itemCode = $productionBatchModel->item_code;
                $subLocationId = $warehouseBulkData->sub_location_id;
                $bulkUniqueId = implode('-', [$referenceNumber, $itemId]);
                $warehouseReceivingModel = WarehouseReceivingModel::select([
                    'reference_number',
                    'item_code',
                    DB::raw('SUM(received_quantity) as received_quantity'),
                    DB::raw('SUM(JSON_LENGTH(discrepancy_data)) as discrepancy_data')
                ])
                    ->where([
                        'reference_number' => $referenceNumber,
                        'item_code' => $itemCode,
                    ])
                    ->groupBy([
                        'reference_number',
                        'item_code'
                    ])
                    ->first();
                if (isset($data[$bulkUniqueId])) {
                    $data[$bulkUniqueId]['production_batches'][$productionBatchId] = json_decode($warehouseBulkData->production_items, true) ?? [];
                } else {
                    $subLocationModel = SubLocationModel::find($subLocationId);
                    $data[$bulkUniqueId] = [
                        'additional_info' => [
                            'warehouse_reference_number' => $referenceNumber,
                            'rack_code' => $subLocationModel->code ?? null,
                            'slid' => $subLocationModel->id ?? null,
                            'item_code' => $itemCode,
                            'item_id' => $itemId,
                            'to_receive_quantity' => $warehouseReceivingModel->discrepancy_data,
                            'received_quantity' => $warehouseReceivingModel->received_quantity ?? 0
                        ],
                        'production_batches' => [$productionBatchId => json_decode($warehouseBulkData->production_items, true)] ?? []
                    ];
                }
            }
            if ($direct_access) {
                return $data;
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onRemoveExisting($createdById)
    {
        $existingWarehouseBulkReceiving = WarehouseBulkReceivingModel::where('created_by_id', $createdById);
        if ($existingWarehouseBulkReceiving->count() > 0) {
            $existingWarehouseBulkReceiving->delete();
        }
    }

    public function onDelete($created_by_id)
    {
        try {
            $existingWarehouseBulkReceiving = WarehouseBulkReceivingModel::where('created_by_id', $created_by_id);
            if ($existingWarehouseBulkReceiving->count() > 0) {
                $existingWarehouseBulkReceiving->delete();
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }

            return $this->dataResponse('success', 200, __('msg.record_not_found'));

        } catch (QueryException $exception) {
            if ($exception->getCode() == 23000) {
                return $this->dataResponse('error', 400, __('msg.delete_failed_fk_constraint', ['modelName' => 'Warehouse Bulk Receiving Model']));
            }
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        }
    }

    public function onSubstandard(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'scanned_items' => 'required|json',
            'reason' => 'required',
            'attachment' => 'nullable',
        ]);

        try {
            $createdById = $fields['created_by_id'];
            $scannedItems = json_decode($fields['scanned_items'], true);
            $warehouseOrganizedBulkItems = [];
            DB::beginTransaction();
            foreach ($scannedItems as $items) {
                $productionBatchId = $items['bid'];
                $productionItemModel = ProductionBatchModel::find($productionBatchId)->productionItems;
                $producedItems = json_decode($productionItemModel->produced_items, true)[$items['sticker_no']];
                $warehouseReferenceNumber = $producedItems['warehouse']['warehouse_receiving']['reference_number'];

                $warehouseBulkReceivingModel = WarehouseBulkReceivingModel::where([
                    'reference_number' => $warehouseReferenceNumber,
                    'production_batch_id' => $productionBatchId,
                    'created_by_id' => $createdById
                ])->first();

                if ($warehouseBulkReceivingModel) {
                    $warehouseBulkProductionItems = json_decode($warehouseBulkReceivingModel->production_items, true);

                    foreach ($warehouseBulkProductionItems as $key => &$warehouseBulkItems) {
                        if ($warehouseBulkItems['bid'] == $items['bid'] && $warehouseBulkItems['sticker_no'] == $items['sticker_no']) {
                            if (isset($warehouseOrganizedBulkItems["$warehouseReferenceNumber-{$warehouseBulkItems['bid']}"])) {
                                $warehouseOrganizedBulkItems["$warehouseReferenceNumber-{$warehouseBulkItems['bid']}"][] = $items;
                            } else {
                                $warehouseOrganizedBulkItems["$warehouseReferenceNumber-{$warehouseBulkItems['bid']}"] = [$items];
                            }

                            unset($warehouseBulkProductionItems[$key]);
                            break;
                        }
                    }
                    $warehouseBulkProductionItems = array_values($warehouseBulkProductionItems);
                    $warehouseBulkReceivingModel->production_items = json_encode($warehouseBulkProductionItems);
                    $warehouseBulkReceivingModel->save();
                }

            }
            $this->onUpdateSubstandardItems($warehouseOrganizedBulkItems, $createdById, $fields['reason'], $fields['attachment'] ?? null);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.update_failed'));
        }
    }

    public function onUpdateSubstandardItems($warehouseOrganizedBulkItems, $createdById, $reason, $attachment = null)
    {
        try {
            foreach ($warehouseOrganizedBulkItems as $warehouseReferenceKey => $warehouseScannedItems) {
                $warehouseReceivingKey = explode('-', $warehouseReferenceKey);
                $referenceNumber = $warehouseReceivingKey[0];
                $productionBatchId = $warehouseReceivingKey[1];

                $warehouseBulkReceivingModel = WarehouseBulkReceivingModel::where([
                    'reference_number' => $referenceNumber,
                    'production_batch_id' => $productionBatchId,
                    'created_by_id' => $createdById
                ])->first();

                $warehouseProductionItems = $warehouseBulkReceivingModel->production_items;
                foreach ($warehouseScannedItems as $substandardItem) {
                    $productionBatchId = $substandardItem['bid'];
                    $productionBatchModel = ProductionBatchModel::find($productionBatchId);
                    $productionOrderId = $productionBatchModel->production_order_id;
                    $itemCode = $productionBatchModel->item_code;
                    $productionItemModel = $productionBatchModel->productionItems;
                    $inclusionArray = [2];
                    $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItemModel->produced_items, true), $substandardItem['sticker_no'], $inclusionArray, []);

                    if ($flag) {
                        $warehouseReceivingModel = WarehouseReceivingModel::where([
                            'item_code' => $itemCode,
                            'production_batch_id' => $productionBatchId,
                            'production_order_id' => $productionOrderId,
                            'reference_number' => $referenceNumber
                        ])->first();
                        if ($warehouseReceivingModel) {
                            $warehouseReceivingProductionItems = json_decode($warehouseReceivingModel->produced_items, true);
                            $discrepancyData = json_decode($warehouseReceivingModel->discrepancy_data, true) ?? [];
                            if (isset($discrepancyData[$substandardItem['sticker_no']])) {
                                unset($discrepancyData[$substandardItem['sticker_no']]);
                            }

                            $mergedItems = [];
                            if (count(json_decode($warehouseReceivingModel->substandard_data, true) ?? []) > 0) {
                                $mergedItems[] = json_decode($warehouseReceivingModel->substandard_data, true);
                            }

                            $mergedItems[] = $substandardItem;
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

                $locationId = 3; // Warehouse Receiving
                $substandardController = new SubStandardItemController();
                $substandardRequest = new Request([
                    'created_by_id' => $createdById,
                    'scanned_items' => json_encode($warehouseScannedItems),
                    'reason' => $reason,
                    'attachment' => $attachment,
                    'location_id' => $locationId,
                ]);
                $substandardController->onCreate($substandardRequest);

                $warehouseReceivingController = new WarehouseReceivingController();
                $createPutAwayRequest = new Request([
                    'created_by_id' => $createdById,
                    'action' => '0',
                    'bulk_data' => $warehouseProductionItems,
                ]);
                $warehouseReceivingController->onUpdate($createPutAwayRequest, $referenceNumber);
            }
            WarehouseBulkReceivingModel::where('created_by_id', $createdById)->delete();
            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onCreatePutAway(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $warehouseBulkReceiving = $this->onGetAll($createdById, true);

            foreach ($warehouseBulkReceiving as $warehouseReferenceKey => $warehouseValues) {
                $explodeReferenceKey = explode('-', $warehouseReferenceKey);
                $referenceNumber = $explodeReferenceKey[0];

                foreach ($warehouseValues['production_batches'] as $productionBatchId => $productionBatches) {
                    $warehouseReceivingModel = WarehouseReceivingModel::where([
                        'reference_number' => $referenceNumber,
                        'production_batch_id' => $productionBatchId,
                    ])->first();
                    if ($warehouseReceivingModel) {
                        $warehouseReceivingModel->status = 1; // completed
                        $warehouseReceivingModel->completed_at = now();
                        $warehouseReceivingModel->save();
                    }
                    $warehouseReceivingController = new WarehouseReceivingController();
                    $createPutAwayRequest = new Request([
                        'created_by_id' => $createdById,
                        'action' => '0',
                        'bulk_data' => json_encode($productionBatches),
                    ]);
                    $warehouseReceivingController->onUpdate($createPutAwayRequest, $referenceNumber);
                    DB::commit();
                }

            }
            WarehouseBulkReceivingModel::where('created_by_id', $createdById)->delete();
            return $this->dataResponse('success', 201, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }

    public function onItemCheckHoldInactiveDone($producedItems, $itemKey, $inclusionArray, $exclusionArray)
    {
        $inArrayFlag = count($inclusionArray) > 0 ?
            in_array($producedItems[$itemKey]['status'], $inclusionArray) :
            !in_array($producedItems[$itemKey]['status'], $exclusionArray);
        return $producedItems[$itemKey]['sticker_status'] != 0 && $inArrayFlag;
    }
}
