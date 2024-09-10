<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\InventoryKeeping\StockTransferItemModel;
use App\Models\WMS\InventoryKeeping\StockTransferListModel;
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

    public function onScanSelectedItems(Request $request, $id)
    {
        $fields = $request->validate([
            'scanned_item' => 'required|json',
            'sub_location_id' => 'required|exists:wms_storage_sub_locations,id',
            'updated_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $updateById = $fields['updated_by_id'];
            $subLocationId = $fields['sub_location_id'];
            if (!$this->onCheckAvailability($subLocationId, false)) {
                throw new Exception('Sub Location Unavailable');
            }
            $scannedItem = json_decode($fields['scanned_item'], true);
            $selectedItemForTransfer = [];
            foreach ($scannedItem as &$value) {
                $productionItemModel = ProductionItemModel::where('production_batch_id', $value['bid'])->first();
                $productionItems = json_decode($productionItemModel->produced_items, true);
                $productionItems[$value['sticker_no']];

                if ($productionItems[$value['sticker_no']]['status'] != 13) { // Stored
                    continue;
                }

                $productionItems[$value['sticker_no']]['status'] = 14; // For Transfer
                $productionItemModel->produced_items = json_encode($productionItems);
                $productionItemModel->save();
                $this->createProductionLog(ProductionItemModel::class, $productionItemModel->id, $productionItems[$value['sticker_no']], $updateById, 1, $value['sticker_no']);
                $selectedItemForTransfer[] = $value;
                unset($value);
            }

            $stockTransferItemModel = StockTransferItemModel::find($id);
            $existingSelectedItems = json_decode($stockTransferItemModel->selected_items, true) ?? [];
            if (count($existingSelectedItems) > $stockTransferItemModel->transfer_quantity || count($scannedItem) > $stockTransferItemModel->transfer_quantity) {
                throw new Exception('Selected Items Exceed Transfer Quantity');
            }
            if ($stockTransferItemModel) {
                // STOCK TRANSFER LIST UPDATE
                $stockTransferListModel = $stockTransferItemModel->stockTransferList;
                $stockTransferListModel->status = 1;
                $stockTransferListModel->save();
                $this->createWarehouseLog(null, null, StockTransferListModel::class, $stockTransferListModel->id, $stockTransferListModel->getAttributes(), $updateById, 1);

                // STOCK TRANSFER ITEM & QUANTITY UPDATE
                $stockTransferItemModel->status = 1;
                $stockTransferItemModel->save();
                $this->createWarehouseLog(null, null, StockTransferItemModel::class, $stockTransferItemModel->id, $stockTransferItemModel->getAttributes(), $updateById, 1);

                $mergedItemToTransfer = array_merge($existingSelectedItems, $selectedItemForTransfer);

                $stockTransferItemModel->selected_items = json_encode($mergedItemToTransfer);
                $stockTransferItemModel->updated_by_id = $fields['updated_by_id'];
                $stockTransferItemModel->temporary_storage_id = $subLocationId;
                $stockTransferItemModel->save();
                $this->createWarehouseLog(null, null, StockTransferItemModel::class, $stockTransferItemModel->id, $stockTransferItemModel->getAttributes(), $fields['updated_by_id'], 1);

                $this->onQueueStorage($updateById, $selectedItemForTransfer, $subLocationId, false, null, null, null, $stockTransferListModel->reference_number, 1);
                DB::commit();
                return $this->dataResponse('success', 200, 'Stock Transfer Item ' . __('msg.update_success'));

            }
            return $this->dataResponse('error', 200, 'Stock Transfer Item ' . __('msg.record_not_found'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
