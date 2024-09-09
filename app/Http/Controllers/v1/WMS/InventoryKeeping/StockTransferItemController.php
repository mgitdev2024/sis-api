<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\StockTransferItemModel;
use App\Traits\WMS\QueueSubLocationTrait;
use App\Traits\WMS\WarehouseLogTrait;
use Illuminate\Http\Request;

use Exception, DB;
class StockTransferItemController extends Controller
{
    use WarehouseLogTrait, QueueSubLocationTrait;

    public function onGetById($id, $is_check_location_only = 0)
    {
        try {
            $stockTransferItemModel = StockTransferItemModel::find($id);

            if ($stockTransferItemModel) {
                $data = [
                    'origin_location_details' => [],
                ];

                switch ($is_check_location_only) {
                    case 0:
                        $data['item_details'] = [];
                        $data['transfer_details'] = [];
                        $data['item_details'] = [
                            'reference_number' => $stockTransferItemModel->stockTransferList->reference_number,
                            'item_code' => $stockTransferItemModel->item_code,
                            'item_description' => $stockTransferItemModel->ItemMasterdata->description,
                            'transfer_quantity' => $stockTransferItemModel->transfer_quantity,
                        ];
                        $transferredItems = json_decode($stockTransferItemModel->transferred_items, true);
                        $substandardItems = json_decode($stockTransferItemModel->substandard_items, true);
                        $transferredBox = count($transferredItems);
                        $substandardBox = count($substandardItems);
                        $transferredQuantity = array_sum(array_column($transferredItems, 'q'));
                        $substandardQuantity = array_sum(array_column($substandardItems, 'q'));

                        $data['transfer_details'] = [
                            'transferred_quantity' => ["box" => $transferredBox, "quantity" => $transferredQuantity],
                            'substandard_quantity' => ["box" => $substandardBox, "quantity" => $substandardQuantity],
                            'remaining_quantity' => $stockTransferItemModel->transfer_quantity - ($transferredBox + $substandardBox),
                        ];

                        $data['origin_location_details'] = [
                            'zone' => $stockTransferItemModel->zone->short_name,
                            'zone_id' => $stockTransferItemModel->zone->id,
                            'sub_location' => $stockTransferItemModel->subLocation->code,
                            'sub_location_id' => $stockTransferItemModel->subLocation->id,
                            'layer' => $stockTransferItemModel->layer,
                        ];
                        break;
                    default:
                        $data['origin_location_details'] = [
                            'zone' => $stockTransferItemModel->zone->short_name,
                            'zone_id' => $stockTransferItemModel->zone->id,
                            'sub_location' => $stockTransferItemModel->subLocation->code,
                            'sub_location_id' => $stockTransferItemModel->subLocation->id,
                            'layer' => $stockTransferItemModel->layer,
                        ];
                        break;
                }

                return $this->dataResponse('success', 200, 'Stock Transfer Item', $data);
            }
            return $this->dataResponse('error', 200, 'Stock Transfer Item ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Stock Transfer Item ' . __('msg.record_not_found'));
        }
    }

    public function onUpdateSelectedItems(Request $request, $id)
    {
        $fields = $request->validate([
            'scanned_item' => 'required|json',
            'sub_location_id' => 'required|exists:wms_storage_sub_locations,id',
            'updated_by_id' => 'required',
        ]);
        try {
            DB::commit();
            $updateById = $fields['updated_by_id'];
            $subLocationId = $fields['sub_location_id'];
            if (!$this->onCheckAvailability($subLocationId, false)) {
                throw new Exception('Sub Location Unavailable');
            }
            $scannedItem = json_decode($fields['scanned_item'], true);
            $this->onQueueStorage($updateById, $scannedItem, $subLocationId, false);

            $stockTransferItemModel = StockTransferItemModel::find($id);
            $stockTransferItemModel->selected_items = json_encode($fields['scanned_item']);
            $stockTransferItemModel->updated_by_id = $fields['updated_by_id'];
            $stockTransferItemModel->save();
            $this->createWarehouseLog(null, null, StockTransferItemModel::class, $stockTransferItemModel->id, $stockTransferItemModel->getAttributes(), $fields['updated_by_id'], 1);
            DB::commit();
            return $this->dataResponse('success', 200, 'Stock Transfer Item ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
