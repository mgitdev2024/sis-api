<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\StockTransfer;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\StockTransfer\StockTransferCacheModel;
use App\Models\WMS\InventoryKeeping\StockTransfer\StockTransferItemModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Traits\ResponseTrait;
use App\Traits\WMS\InventoryMovementTrait;
use Illuminate\Http\Request;
use DB, Exception;

class StockTransferCacheController extends Controller
{
    use ResponseTrait, InventoryMovementTrait;

    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            // 'reason' => 'required|string',
            'items_to_transfer' => 'required_if:is_transfer_all,0|json',
            'zone_id' => 'required|integer|exists:wms_storage_zones,id',
            'is_transfer_all' => 'required|boolean',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $totalRequestedItemCount = 0;
            $zoneId = $fields['zone_id'];

            $itemsToTransfer = json_decode($fields['items_to_transfer'] ?? null, true);
            if ($itemsToTransfer == null) {
                $itemsToTransfer = $this->onTransferAllItems($zoneId);
            }

            $stockTransferListCache = new StockTransferCacheModel();
            $stockTransferItemCache = [];
            foreach ($itemsToTransfer as $subLocationValue) {
                foreach ($subLocationValue['layers'] as $layers) {
                    $totalRequestedItemCount += $layers['transfer_quantity'];
                    $stockTransferItemCache[] = [
                        'zone_id' => $zoneId,
                        'sub_location_id' => $subLocationValue['sub_location_id'],
                        'item_id' => $layers['item_id'],
                        'initial_stock' => $layers['initial_stock'],
                        'transfer_quantity' => $layers['transfer_quantity'],
                        'layer' => $layers['layer'],
                        'origin_location' => StockTransferItemModel::onGenerateOriginLocation($subLocationValue['sub_location_id'], $layers['layer']),
                        'created_by_id' => $createdById
                    ];
                }
            }

            $stockTransferListCache->requested_item_count = $totalRequestedItemCount;
            // $stockTransferListCache->reason = $fields['reason'];
            $stockTransferListCache->created_by_id = $createdById;
            $stockTransferListCache->stock_transfer_items = json_encode($stockTransferItemCache);
            $stockTransferListCache->save();
            DB::commit();
            return $this->dataResponse('success', 200, 'Stock Request ' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetCache($created_by_id)
    {
        try {
            $stockTransferCache = StockTransferCacheModel::where('created_by_id', $created_by_id)
                ->orderBy('id', 'DESC')
                ->first();
            if ($stockTransferCache) {
                $itemsToTransfer = json_decode($stockTransferCache->stock_transfer_items, true);
                foreach ($itemsToTransfer as $key => $item) {
                    $itemMasterData = ItemMasterdataModel::find($item['item_id']);
                    $itemsToTransfer[$key]['item_description'] = $itemMasterData->description;
                    $itemsToTransfer[$key]['item_code'] = $itemMasterData->item_code;
                }
                $data = [
                    // 'reason' => $stockTransferCache->reason,
                    'stock_transfer_items' => $itemsToTransfer,
                    'requested_item_count' => $stockTransferCache->requested_item_count,
                ];
                return $this->dataResponse('success', 200, 'Stock Transfer Cache', $data);
            } else {
                return $this->dataResponse('error', 400, 'Stock Transfer Cache ' . __('msg.record_not_found'));
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onTransferAllItems($zoneId)
    {
        $zoneItems = $this->onGetZoneStoredItems($zoneId, true);
        return $zoneItems;
    }
}
