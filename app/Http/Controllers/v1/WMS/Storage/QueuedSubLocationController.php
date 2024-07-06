<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Traits\ResponseTrait;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use Exception;

class QueuedSubLocationController extends Controller
{
    use QueueSubLocationTrait, ResponseTrait;
    public function onGetCurrent($sub_location_id, $layer)
    {
        try {
            return $this->onGetSubLocationDetails($sub_location_id, $layer, true);
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
                    ];
                }
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }

    }
}
