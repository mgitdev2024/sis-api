<?php

namespace App\Http\Controllers\v1\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreReceivingInventoryModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Traits\ResponseTrait;
class StoreReceivingInventoryController extends Controller
{
    use ResponseTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_name' => 'required',
            'created_by_id' => 'required',
            'consolidated_data' => 'required'
        ]);
        try {
            $createdByName = $fields['created_by_name'];
            $createdById = $fields['created_by_id'];
            $response = Http::withToken($request->bearerToken())
                ->get(env('MGIOS_URL') . '/check-token');
            if (!$response->successful()) {
                return $this->dataResponse('error', 404, 'Unauthorized Access');
            }

            // Decode consolidated data and insert them into the store_receiving_inventory table
            $consolidatedData = json_decode($fields['consolidated_data'], true);
            $consolidatedOrderId = $consolidatedData['consolidated_order_id'];
            $warehouseCode = $consolidatedData['warehouse_code'];
            $insertData = [];

            foreach ($consolidatedData['sessions'] as $storeOrders) {
                $storeCode = $storeOrders['store_code'];
                $storeName = $storeOrders['store_name'];
                $deliveryDate = $storeOrders['delivery_date'];
                $deliveryType = $storeOrders['delivery_type'];
                $orderDate = $storeOrders['order_date'];

                if (isset($storeOrders['ordered_items'])) {
                    foreach ($storeOrders['ordered_items'] as $orderedItems) {
                        $insertData[] = [
                            'consolidated_order_id' => $consolidatedOrderId,
                            'warehouse_code' => $warehouseCode,
                            'store_code' => $storeCode,
                            'store_name' => $storeName,
                            'delivery_date' => $deliveryDate,
                            'delivery_type' => $deliveryType,
                            'order_date' => $orderDate,
                            'item_code' => $orderedItems['item_code'],
                            'order_quantity' => $orderedItems['order_quantity'],
                            'received_quantity' => 0,
                            'received_items' => json_encode([]),
                            'created_by_id' => $createdById,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Bulk insert to speed up
            if (!empty($insertData)) {
                StoreReceivingInventoryModel::insert($insertData);
            }

            return $this->dataResponse('success', 200, __('msg.create_success'), );
        } catch (Exception $e) {
            \Log::info($e->getMessage());
            return $this->dataResponse('error', 404, __('msg.create_failed'), $e->getMessage());
        }
    }

    public function onGetCurrent($status, $store_code)
    {
        try {
            $storeReceivingInventoryModel = StoreReceivingInventoryModel::where([
                'status' => $status,
                'store_code' => $store_code
            ]);
        } catch (Exception $e) {

        }
    }
}
