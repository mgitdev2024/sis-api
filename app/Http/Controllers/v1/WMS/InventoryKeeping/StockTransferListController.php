<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\StockTransferCacheModel;
use App\Models\WMS\InventoryKeeping\StockTransferCancelledModel;
use App\Models\WMS\InventoryKeeping\StockTransferItemModel;
use App\Models\WMS\InventoryKeeping\StockTransferListModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Traits\WMS\WarehouseLogTrait;
use App\Traits\WMS\WmsCrudOperationsTrait;
use App\Traits\Admin\CredentialsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;
class StockTransferListController extends Controller
{
    use WmsCrudOperationsTrait, CredentialsTrait, WarehouseLogTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'reason' => 'required|string',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $stockTransferListModel = new StockTransferListModel();
            $referenceCode = StockTransferListModel::onGenerateStockRequestReferenceNumber();
            $latestId = StockTransferListModel::latest()->value('id') + 1;
            $stockTransferCache = StockTransferCacheModel::where('created_by_id', $createdById)
                ->orderBy('id', 'DESC')
                ->first();
            if ($stockTransferCache) {
                $itemsToTransfer = json_decode($stockTransferCache->stock_transfer_items, true);

                foreach ($itemsToTransfer as $items) {
                    $stockTransferItemModel = new StockTransferItemModel();
                    $stockTransferItemModel->stock_transfer_list_id = $latestId;
                    $stockTransferItemModel->zone_id = $items['zone_id'];
                    $stockTransferItemModel->sub_location_id = $items['sub_location_id'];
                    $stockTransferItemModel->item_id = $items['item_id'];
                    $stockTransferItemModel->origin_location = $items['origin_location'];
                    $stockTransferItemModel->initial_stock = $items['initial_stock'];
                    $stockTransferItemModel->transfer_quantity = $items['transfer_quantity'];
                    $stockTransferItemModel->layer = $items['layer'];
                    $stockTransferItemModel->created_by_id = $createdById;
                    $stockTransferItemModel->save();
                    $this->createWarehouseLog(null, null, StockTransferItemModel::class, $stockTransferItemModel->id, $stockTransferItemModel->getAttributes(), $createdById, 0);
                }

                $stockTransferListModel->reference_number = $referenceCode;
                $stockTransferListModel->requested_item_count = $stockTransferCache->requested_item_count;
                $stockTransferListModel->reason = $fields['reason'];
                $stockTransferListModel->created_by_id = $createdById;
                $stockTransferListModel->save();

                StockTransferCacheModel::where('created_by_id', $createdById)
                    ->orderBy('id', 'DESC')
                    ->delete();
                $this->createWarehouseLog(null, null, StockTransferListModel::class, $stockTransferListModel->id, $stockTransferListModel->getAttributes(), $createdById, 0);

                DB::commit();
                return $this->dataResponse('success', 200, 'Stock Request ' . __('msg.create_success'));
            } else {
                return $this->dataResponse('success', 200, 'Stock Request ' . __('msg.create_failed'));

            }
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetAll($status = null)
    {
        try {
            $stockTransferListModel = StockTransferListModel::orderBy('created_at', 'DESC');
            if ($status != 'complete') {
                $stockTransferListModel->whereNotIn('status', [0, 3]);
            } else {
                $stockTransferListModel->where('status', 3);
            }
            $stockTransferListModel = $stockTransferListModel->get();

            foreach ($stockTransferListModel as $item) {
                $item['formatted_date'] = date('Y-m-d h:i:A', strtotime($item['created_at']));
            }
            if (count($stockTransferListModel) > 0) {
                return $this->dataResponse('success', 200, 'Stock Transfer List', $stockTransferListModel);
            }
            return $this->dataResponse('error', 200, 'Stock Transfer List ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Stock Transfer List ' . __('msg.record_not_found'));
        }
    }

    public function onGetById($id)
    {
        try {
            $stockTransferListModel = StockTransferListModel::with('stockTransferItems')->find($id);
            foreach ($stockTransferListModel->stockTransferItems as $item) {
                $item->item_description = ItemMasterdataModel::find($item['item_id'])->value('description');
            }
            $stockTransferListModel->formatted_date = date('Y-m-d h:i:A', strtotime($stockTransferListModel->created_at));
            $fullName = $this->onGetName($stockTransferListModel->created_by_id);
            $stockTransferListModel->requested_by = $fullName;
            if ($stockTransferListModel) {
                return $this->dataResponse('success', 200, 'Stock Transfer List', $stockTransferListModel);
            }
            return $this->dataResponse('error', 200, 'Stock Transfer List ' . __('msg.not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onCancel(Request $request, $id)
    {
        try {
            $request->merge(['stock_transfer_list_id' => $id]);

            $rules = [
                'stock_transfer_list_id' => 'required|integer',
                'reason' => 'required|string',
                'attachment' => 'nullable',
                'created_by_id' => 'required',
            ];
            $stockTransferListModel = StockTransferListModel::find($id);

            switch ($stockTransferListModel->status) {
                case 1:
                    $this->createRecord(StockTransferCancelledModel::class, $request, $rules, 'Stock Transfer List', 0);

                    $stockTransferListModel = StockTransferListModel::find($id);
                    $stockTransferListModel->status = 0; // Cancelled status
                    $stockTransferListModel->save();
                    return $this->dataResponse('success', 200, 'Stock Transfer List ' . __('msg.update_success'));

                default:
                    return $this->dataResponse('error', 200, 'Stock Transfer List ' . __('msg.update_failed'));
            }


        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    // Below are for Stock Transfer Warehouse Stockman
    public function onGetStockRequestList($statusId)
    {
        try {

            $stockTransferListModel = StockTransferListModel::query();

            if ($statusId == 'pending') {
                $stockTransferListModel->whereIn('status', [1, 2])->orderBy('created_at', 'DESC');
            } else {
                $stockTransferListModel->where('status', $statusId)->orderBy('created_at', 'DESC');
            }
            $stockTransferListModel = $stockTransferListModel->get();
            foreach ($stockTransferListModel as $item) {
                $item['formatted_date'] = date('Y-m-d', strtotime($item['created_at']));
                $item['zone'] = $item->stockTransferItems[0]->zone->short_name;
            }
            if (count($stockTransferListModel) > 0) {
                return $this->dataResponse('success', 200, 'Stock Transfer List', $stockTransferListModel);
            }
            return $this->dataResponse('error', 200, 'Stock Transfer List ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Stock Transfer List ' . __('msg.record_not_found'));
        }
    }

    public function onGetStockRequestById($id)
    {
        try {
            $stockTransferListModel = StockTransferListModel::with('stockTransferItems')->find($id);
            foreach ($stockTransferListModel->stockTransferItems as $item) {
                $transferredItems = json_decode($item->transferred_items, true);
                $transferredQuantity = is_array($transferredItems) ? count($transferredItems) : 0;

                $substandardItems = json_decode($item->substandard_items, true);
                $substandardQuantity = is_array($substandardItems) ? count($substandardItems) : 0;

                $item->transferred_quantity = $transferredQuantity;
                $item->substandard_quantity = $substandardQuantity;
            }
            $stockTransferListModel->formatted_date = date('Y-m-d h:i:A', strtotime($stockTransferListModel->created_at));
            $fullName = $this->onGetName($stockTransferListModel->created_by_id);
            $stockTransferListModel->requested_by = $fullName;
            if ($stockTransferListModel) {
                return $this->dataResponse('success', 200, 'Stock Transfer List', $stockTransferListModel);
            }
            return $this->dataResponse('error', 200, 'Stock Transfer List ' . __('msg.not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Stock Transfer List ' . __('msg.record_not_found'));
        }
    }
}

