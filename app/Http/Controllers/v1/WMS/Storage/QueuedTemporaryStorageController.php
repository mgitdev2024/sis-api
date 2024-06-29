<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use App\Traits\WMS\WmsCrudOperationsTrait;

class QueuedTemporaryStorageController extends Controller
{
    use WmsCrudOperationsTrait, QueueSubLocationTrait;
    public function onGetCurrent($sub_location_id)
    {
        $whereFields = [
            'sub_location_id' => $sub_location_id
        ];
        $this->readCurrentRecord(QueuedTemporaryStorageModel::class, null, $whereFields, null, null, 'Queued Temporary Storage');
    }

    public function onGetStatus($id)
    {
        $data = $this->onCheckAvailability($id, false);
        return $this->dataResponse('success', 200, __('msg.record_found'), $data);
    }

    public function onGetItems($sub_location_id)
    {
        try {
            $items = $this->onGetQueuedItems($sub_location_id, false);
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
                if ($producedItem['status'] == 2) {
                    $data['production_items'][] = [
                        'bid' => $itemDetails['bid'],
                        'item_code' => $itemCode,
                        'sticker_no' => $stickerNumber,
                    ];
                }


            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }

    }
}
