<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Warehouse\WarehouseBulkReceivingModel;
use App\Models\WMS\Warehouse\WarehouseForReceiveModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use Exception;
use Illuminate\Http\Request;
use App\Traits\WMS\QueueSubLocationTrait;
use DB;
class WarehouseBulkReceivingController extends Controller
{
    use QueueSubLocationTrait;

    public function onGetTemporaryStorageItems($sub_location_id, $status)
    {
        try {
            $items = $this->onGetQueuedItems($sub_location_id, false);
            $combinedItems = array_merge(...$items);
            $data = [];
            foreach ($combinedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $itemId = $productionOrderToMake->itemMasterdata->id;
                $stickerNumber = $itemDetails['sticker_no'];
                $producedItem = json_decode($productionBatch->productionItems->produced_items, true)[$stickerNumber];
                if ($producedItem['status'] == $status) {
                    $subLocationId = $producedItem['sub_location']['sub_location_id'];
                    $warehouseReceivingModel = WarehouseReceivingModel::where([
                        'reference_number' => $producedItem['warehouse']['warehouse_receiving']['reference_number'],
                        'production_batch_id' => $productionBatch->id,
                    ])->first();
                    $data[] = [
                        'bid' => $itemDetails['bid'],
                        'item_code' => $itemCode,
                        'item_id' => $itemId,
                        'sticker_no' => $stickerNumber,
                        'q' => $producedItem['q'],
                        'batch_code' => $producedItem['batch_code'],
                        'parent_batch_code' => $producedItem['parent_batch_code'],
                        'slid' => $subLocationId,
                        'rack_code' => SubLocationModel::find($subLocationId)->code,
                        'warehouse' => [
                            'warehouse_receiving' => [
                                'id' => $warehouseReceivingModel->id,
                                'reference_number' => $warehouseReceivingModel->reference_number,
                                'received_quantity' => $warehouseReceivingModel->received_quantity,
                                'to_receive_quantity' => count(json_decode($warehouseReceivingModel->discrepancy_data, true) ?? [])
                            ]
                        ]
                    ];
                }
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onPutAway(Request $request)
    {
        $fields = $request->validate([
            'warehouse_production_items' => 'required|json',
            'created_by_id' => 'required',
        ]);
        try {
            $lastGroupTransactionNumber = WarehouseForReceiveModel::getLastGroupTransactionNumber();
            $groupTransactionNumber = $lastGroupTransactionNumber + 1;
            $warehouseProductionItems = json_decode($fields['warehouse_production_items'], true);
            $createdById = $fields['created_by_id'];
            foreach ($warehouseProductionItems as $warehouseKey => $warehouseItems) {
                $keyExplode = explode('-', $warehouseKey);
                $referenceNumber = $keyExplode[0];
                $batchId = $keyExplode[1];
                $subLocationId = $keyExplode[2];
                $itemCode = $warehouseItems['additional_info']['item_code'];

                $warehouseForReceiveModel = new WarehouseForReceiveModel();
                $warehouseForReceiveModel->reference_number = $referenceNumber;

            }
        } catch (Exception $exception) {

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
            foreach ($warehouseProductionItems as $warehouseKey => $warehouseItems) {
                $keyExplode = explode('-', $warehouseKey);
                $referenceNumber = $keyExplode[0];
                $batchId = $keyExplode[1];
                $subLocationId = $keyExplode[2];
                // $itemCode = $warehouseItems['additional_info']['item_code'];
                $warehouseBulkReceivingModel = new WarehouseBulkReceivingModel();
                $warehouseBulkReceivingModel->reference_number = $referenceNumber;
                $warehouseBulkReceivingModel->production_batch_id = $batchId;
                $warehouseBulkReceivingModel->sub_location_id = $subLocationId;
                $warehouseBulkReceivingModel->production_items = json_encode($warehouseItems['items']);
                $warehouseBulkReceivingModel->created_by_id = $createdById;
                $warehouseBulkReceivingModel->save();
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onGetAll($created_by_id)
    {
        try {
            $warehouseBulkReceivingModel = WarehouseBulkReceivingModel::where('created_by_id', $created_by_id)->get();
            $data = [];

            foreach ($warehouseBulkReceivingModel as $warehouseBulkData) {
                $referenceNumber = $warehouseBulkData->reference_number;
                $productionBatchId = $warehouseBulkData->production_batch_id;
                $subLocationId = $warehouseBulkData->sub_location_id;
                $bulkUniqueId = implode('-', [$referenceNumber, $productionBatchId, $subLocationId]);
                $warehouseReceivingModel = WarehouseReceivingModel::where([
                    'reference_number' => $referenceNumber,
                    'production_batch_id' => $productionBatchId,
                ])->first();
                $data[$bulkUniqueId][] = [
                    "additional_info" => [
                        "warehouse_reference_number" => $referenceNumber,
                        "sub_location_code" => SubLocationModel::find($subLocationId)->code,
                        "item_code" => ProductionBatchModel::find($productionBatchId)->item_code,
                        "for_receive" => count(json_decode($warehouseReceivingModel->discrepancy_data ?? null, true) ?? []),
                        "received" => $warehouseReceivingModel->received_quantity ?? 0
                    ],
                    "items" => json_decode($warehouseBulkData->production_items, true) ?? []
                ];
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}

/*
{
    "8000002-2-3": [
        {
            "additional_info": [
                {
                    "warehouse_reference_number": "8000002",
                    "sub_location_code": "RCK003",
                    "item_code": "CH MN",
                    "for_receive": 0
                }
            ],
            "items": [
                {
                    "bid": 2,
                    "q": 1,
                    "sticker_no": 3
                }
            ]
        }
    ],
    "8000002-2-4": [
        {
            "additional_info": [
                {
                    "warehouse_reference_number": "8000002",
                    "sub_location_code": "RCK003",
                    "item_code": "CH MN",
                    "for_receive": 0
                }
            ],
            "items": [
                {
                    "bid": 2,
                    "q": 1,
                    "sticker_no": 3
                }
            ]
        }
    ]
}
*/
