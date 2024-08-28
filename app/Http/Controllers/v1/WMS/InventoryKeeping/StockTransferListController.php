<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\StockTransferCacheModel;
use App\Models\WMS\InventoryKeeping\StockTransferCancelledModel;
use App\Models\WMS\InventoryKeeping\StockTransferItemModel;
use App\Models\WMS\InventoryKeeping\StockTransferListModel;
use App\Traits\WMS\WarehouseLogTrait;
use App\Traits\WMS\WmsCrudOperationsTrait;
use App\Traits\Credentials\CredentialsTrait;
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
                    $stockTransferItemModel->item_code = $items['item_code'];
                    $stockTransferItemModel->origin_location = $items['origin_location'];
                    $stockTransferItemModel->initial_stock = $items['initial_stock'];
                    $stockTransferItemModel->transfer_quantity = $items['transfer_quantity'];
                    $stockTransferItemModel->layer = $items['layer'];
                    $stockTransferItemModel->created_by_id = $createdById;
                    $stockTransferItemModel->save();
                    $this->createWarehouseLog(null, null, StockTransferItemModel::class, $stockTransferItemModel->id, $stockTransferItemModel->getAttributes(), $createdById, 1);
                }

                $stockTransferListModel->reference_number = $referenceCode;
                $stockTransferListModel->requested_item_count = $stockTransferCache->requested_item_count;
                $stockTransferListModel->reason = $stockTransferCache->reason;
                $stockTransferListModel->created_by_id = $createdById;
                $stockTransferListModel->save();

                StockTransferCacheModel::where('created_by_id', $createdById)
                    ->orderBy('id', 'DESC')
                    ->delete();
                $this->createWarehouseLog(null, null, StockTransferListModel::class, $stockTransferListModel->id, $stockTransferListModel->getAttributes(), $createdById, 1);

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

    public function onGetAll($status)
    {
        try {
            $stockTransferListModel = StockTransferListModel::orderBy('created_at', 'DESC');
            if ($status != 'complete') {
                $stockTransferListModel->whereNotIn('status', [0, 3]);
            } else {
                $stockTransferListModel->where('status', 3);
            }
            $stockTransferListModel = $stockTransferListModel->get();
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
            $stockTransferListModel->formatted_date = date('Y-m-d', strtotime($stockTransferListModel->created_at));
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

            $this->createRecord(StockTransferCancelledModel::class, $request, $rules, 'Stock Transfer List', 0);

            $stockTransferListModel = StockTransferListModel::find($id);
            $stockTransferListModel->status = 0; // Cancelled status
            $stockTransferListModel->save();

            return $this->dataResponse('success', 200, 'Stock Transfer List ' . __('msg.update_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
/*
[
    {
        "sub_location_id": 1,
        "code": "RCK0001",
        "layers":[
            {
                "item_code": "CR 12",
                "initial_stock": 10,
                "transfer_quantity": 5,
                "layer": 1
            },
            {
                "item_code": "CR 12",
                "initial_stock": 10,
                "transfer_quantity": 5,
                "layer": 2
            },
            {
                "item_code": "CR 12",
                "initial_stock": 10,
                "transfer_quantity": 5,
                "layer": 3
            }
        ]
    },
    {
        "sub_location_id": 2,
        "code": "RCK0002",
        "layers":[
            {
                "item_code": "CR 12",
                "initial_stock": 10,
                "transfer_quantity": 5,
                "layer": 1
            },
            {
                "item_code": "MM 6",
                "initial_stock": 10,
                "transfer_quantity": 5,
                "layer": 1
            },
            {
                "item_code": "CR 12",
                "initial_stock": 10,
                "transfer_quantity": 5,
                "layer": 2
            },
            {
                "item_code": "CR 12",
                "initial_stock": 10,
                "transfer_quantity": 5,
                "layer": 3
            }
        ]
    }
]
 */
