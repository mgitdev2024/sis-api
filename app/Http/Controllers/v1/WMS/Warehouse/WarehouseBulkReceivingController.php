<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use Exception;
use Illuminate\Http\Request;
use App\Traits\WMS\QueueSubLocationTrait;
class WarehouseBulkReceivingController extends Controller
{
    use QueueSubLocationTrait;
    public function onCreate(Request $request)
    {
        // 8000001&1&1: [{"bid":212 q"}]
    }

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
}
