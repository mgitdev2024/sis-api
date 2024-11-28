<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransfer;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\InventoryKeeping\StockTransfer\StockTransferItemModel;
use App\Models\WMS\InventoryKeeping\StockTransfer\StockTransferListModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use App\Traits\WMS\QueueSubLocationTrait;
use App\Traits\WMS\WarehouseLogTrait;
use Illuminate\Http\Request;

use Exception, DB;
class StockTransferItemController extends Controller
{
    use WarehouseLogTrait, QueueSubLocationTrait;

    public function onGetById($stock_transfer_item_id, $is_check_location_only = 0)
    {
        try {
            $stockTransferItemModel = StockTransferItemModel::find($stock_transfer_item_id);

            if ($stockTransferItemModel) {
                $data = [
                    'origin_location_details' => [],
                ];

                switch ($is_check_location_only) {
                    case 0:
                        $itemMasterdata = $stockTransferItemModel->ItemMasterdata;
                        $uom = $itemMasterdata->uom_label['short_name'];
                        $primaryConversion = $itemMasterdata->primary_conversion_label['short_name'] ?? null;
                        $data['item_details'] = [];
                        $data['transfer_details'] = [];
                        $data['item_masterdata'] = $itemMasterdata;
                        $data['item_details'] = [
                            'reference_number' => $stockTransferItemModel->stockTransferList->reference_number,
                            'stock_transfer_list_id' => $stockTransferItemModel->stockTransferList->id,
                            'item_code' => $stockTransferItemModel->item_code,
                            'item_description' => $itemMasterdata->description,
                            'transfer_quantity' => $stockTransferItemModel->transfer_quantity,
                        ];
                        $transferredItems = json_decode($stockTransferItemModel->transferred_items, true) ?? [];
                        $substandardItems = json_decode($stockTransferItemModel->substandard_items, true) ?? [];
                        $transferredBox = count($transferredItems);
                        $substandardBox = count($substandardItems);
                        $transferredQuantity = array_sum(array_column($transferredItems, 'q'));
                        $substandardQuantity = array_sum(array_column($substandardItems, 'q'));

                        $data['transfer_details'] = [
                            'transferred_quantity' => [$uom => $transferredBox],
                            'substandard_quantity' => [$uom => $substandardBox],
                            'remaining_quantity' => $stockTransferItemModel->transfer_quantity - ($transferredBox + $substandardBox),
                            'transfer_status' => $stockTransferItemModel->status,
                        ];

                        if ($primaryConversion) {
                            $data['transfer_details']['transferred_quantity'][$primaryConversion] = $transferredQuantity;
                            $data['transfer_details']['substandard_quantity'][$primaryConversion] = $substandardQuantity;
                        }

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

    public function onGetSelectedItems($stock_transfer_item_id)
    {
        try {
            $stockTransferItemModel = StockTransferItemModel::find($stock_transfer_item_id);
            if ($stockTransferItemModel) {
                $selectedItems = json_decode($stockTransferItemModel->selected_items, true) ?? [];
                $filteredItems = array_filter($selectedItems, function ($item) {
                    return !isset($item['status']); // Return only items without status
                });
                $data = [];
                $data['selected_items'] = $filteredItems;

                $temporaryStorage = SubLocationModel::find($stockTransferItemModel->temporary_storage_id);
                $data['temporary_storage_id'] = [
                    'sub_location_id' => $temporaryStorage->id,
                    'sub_location_code' => $temporaryStorage->code,
                ];
                return $this->dataResponse('success', 200, 'Filtered Selected Items', $data);
            }
            return $this->dataResponse('error', 200, 'Selected Items ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Selected Items ' . __('msg.record_not_found'));
        }
    }

    public function onScanSelectedItems(Request $request, $stock_transfer_item_id)
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
            $stockTransferItemModel = StockTransferItemModel::find($stock_transfer_item_id);
            $stockTransferReferenceNumber = $stockTransferItemModel->stockTransferList->reference_number;
            $scannedItem = json_decode($fields['scanned_item'], true);
            $selectedItemForTransfer = [];
            foreach ($scannedItem as &$value) {
                $productionItemModel = ProductionItemModel::where('production_batch_id', $value['bid'])->first();
                $productionItems = json_decode($productionItemModel->produced_items, true);
                $productionItems[$value['sticker_no']];

                if ($productionItems[$value['sticker_no']]['status'] != 13) { // Stored
                    continue;
                }

                $productionItems[$value['sticker_no']]['stock_transfer'] = [
                    'reference_number' => $stockTransferReferenceNumber,
                ];
                $productionItems[$value['sticker_no']]['status'] = 14; // For Transfer
                $productionItemModel->produced_items = json_encode($productionItems);
                $productionItemModel->save();
                $this->createProductionLog(ProductionItemModel::class, $productionItemModel->id, $productionItems[$value['sticker_no']], $updateById, 1, $value['sticker_no']);
                $selectedItemForTransfer[] = $value;
                unset($value);
            }

            $existingSelectedItems = json_decode($stockTransferItemModel->selected_items, true) ?? [];
            if (count($existingSelectedItems) > $stockTransferItemModel->transfer_quantity || count($scannedItem) > $stockTransferItemModel->transfer_quantity) {
                throw new Exception('Selected Items Exceed Transfer Quantity');
            }
            if ($stockTransferItemModel) {
                // STOCK TRANSFER LIST UPDATE
                $stockTransferListModel = $stockTransferItemModel->stockTransferList;
                $stockTransferListModel->status = 2;
                $stockTransferListModel->save();
                $this->createWarehouseLog(null, null, StockTransferListModel::class, $stockTransferListModel->id, $stockTransferListModel->getAttributes(), $updateById, 1);

                // STOCK TRANSFER ITEM & QUANTITY UPDATE
                $stockTransferItemModel->status = 1;
                $stockTransferItemModel->save();
                $this->createWarehouseLog(null, null, StockTransferItemModel::class, $stockTransferItemModel->id, $stockTransferItemModel->getAttributes(), $updateById, 1);

                $mergedItemToTransfer = array_merge($existingSelectedItems, $selectedItemForTransfer);

                $stockTransferItemModel->selected_items = json_encode($mergedItemToTransfer); //
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

    public function onCompleteTransaction(Request $request, $stock_transfer_item_id)
    {
        try {
            $fields = $request->validate([
                'created_by_id' => 'required',
            ]);
            $stockTransferItemModel = StockTransferItemModel::find($stock_transfer_item_id);
            $selectedItems = json_decode($stockTransferItemModel->selected_items, true) ?? [];
            $transferredItems = json_decode($stockTransferItemModel->transferred_items, true) ?? [];
            $substandardItems = json_decode($stockTransferItemModel->substandard_items, true) ?? [];

            $mergedItems = array_merge($transferredItems, $substandardItems);
            $discrepancyItems = [];

            foreach ($selectedItems as $key => $selectedItem) {
                $isFound = false;
                foreach ($mergedItems as $mergedItem) {
                    if (($selectedItem['bid'] == $mergedItem['bid'] && $selectedItem['sticker_no'] == $mergedItem['sticker_no']) && isset($selectedItem['status'])) {
                        $isFound = true;
                        break;
                    }
                }
                if (!$isFound) {
                    $discrepancyItems[] = $selectedItem;
                }
            }
            DB::beginTransaction();
            $stockTransferItemModel->status = 2;
            $stockTransferItemModel->discrepancy_items = json_encode($discrepancyItems);
            $stockTransferItemModel->save();
            $this->createWarehouseLog(null, null, StockTransferListModel::class, $stockTransferItemModel->id, $stockTransferItemModel->getAttributes(), $fields['created_by_id'], 0);

            $stockTransferListItems = StockTransferItemModel::where('stock_transfer_list_id', $stockTransferItemModel->stock_transfer_list_id)->get();
            $temporaryStorageModel = QueuedTemporaryStorageModel::where('sub_location_id', $stockTransferItemModel->temporary_storage_id)->delete();
            $completionCounter = 0;
            foreach ($stockTransferListItems as $items) {
                if ($items->status == 2) {
                    $completionCounter++;
                }
            }

            if ($completionCounter == count($stockTransferListItems)) {
                $stockTransferListModel = $stockTransferItemModel->stockTransferList;
                $stockTransferListModel->status = 3;
                $stockTransferListModel->completed_at = now();
                $stockTransferListModel->save();
                $this->createWarehouseLog(null, null, StockTransferListModel::class, $stockTransferListModel->id, $stockTransferListModel->getAttributes(), $fields['created_by_id'], 0);
            }

            DB::commit();
            return $this->dataResponse('success', 200, 'Stock Transfer List ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
