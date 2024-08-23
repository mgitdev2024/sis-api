<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\StockTransferItemModel;
use App\Models\WMS\InventoryKeeping\StockTransferListModel;
use Illuminate\Http\Request;
use DB;
use Exception;
class StockTransferListController extends Controller
{
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'reason' => 'required|string',
            'items_to_transfer' => 'required|json',
            'zone_id' => 'required|integer|exists:wms_storage_zones,id',
        ]);
        try {
            DB::beginTransaction();
            $stockTransferListModel = new StockTransferListModel();
            $referenceCode = StockTransferListModel::onGenerateStockRequestReferenceNumber();
            $latestId = StockTransferListModel::latest()->value('id') + 1;

            foreach (json_decode($fields['items_to_transfer'], true) as $item) {
                $stockTransferItemModel = new StockTransferItemModel();
                $stockTransferItemModel->stock_transfer_list_id = $latestId;

            }
            $stockTransferListModel->reference_number = $referenceCode;
            $stockTransferListModel->requested_item_count = 0;
            $stockTransferListModel->reason = $fields['reason'];
            $stockTransferListModel->save();
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
/*[
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
