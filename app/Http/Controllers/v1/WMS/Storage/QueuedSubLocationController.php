<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\WMS\Warehouse\WarehouseForPutAwayModel;
use App\Traits\ResponseTrait;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use Exception;

class QueuedSubLocationController extends Controller
{
    use QueueSubLocationTrait, ResponseTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'warehouse_put_away_id' => 'required|exists:wms_warehouse_put_away,id',
            'item_code' => 'required|exists:wms_item_masterdata,item_code',
            'scanned_items' => 'required|json'
        ]);
        try {
            $warehouseForPutAway = WarehouseForPutAwayModel::where([
                'warehouse_put_away_id' => $fields['warehouse_put_away_id'],
                'item_code' => $fields['item_code'],
                'status' => 1
            ])->first();

            if ($warehouseForPutAway) {
                $subLocationId = $warehouseForPutAway->sub_location_id;
                $layerLevel = $warehouseForPutAway->layer_level;

                $scannedItems = json_decode($fields['scanned_items'], true);
            }

            return $this->dataResponse('success', 200, __('msg.record_not_found'));


        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
        /*
        1. Get Warehouse For Put away, compare it with the scanned items.
        1.1 If it match, then true, else continue

        2. Locate the sublocation and layer of the scanned item
        3. Update Stock inventory and stock log
        4. Delete for put away data related to the scanned ref no
        6. item stored
        */
    }

    public function onGetCurrent($sub_location_id, $item_code)
    {
        try {
            $warehouseForPutAway = WarehouseForPutAwayModel::where([
                'item_code' => $item_code,
                'sub_location_id' => $sub_location_id,
                'status' => 1
            ])->first();
            if ($warehouseForPutAway) {
                $subLocationDetails = $this->onGetSubLocationDetails($sub_location_id, $warehouseForPutAway->layer_level, true);
                $productionItems = json_decode($warehouseForPutAway->production_items, true);
                $restructuredArray = [];
                foreach ($productionItems as $item) {
                    $batchCode = $item['batch_code'];
                    $restructuredArray[$batchCode] = $item;
                }

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
}
