<?php

namespace App\Http\Controllers\v1\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Models\Store\StoreReceivingInventoryModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Traits\ResponseTrait;
use DB;
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

            $generatedReferenceNumber = StoreReceivingInventoryModel::onGenerateReferenceNumber($consolidatedOrderId);
            $storeReceivingInventory = StoreReceivingInventoryModel::create([
                'consolidated_order_id' => $consolidatedOrderId,
                'warehouse_code' => $warehouseCode,
                'warehouse_name' => $consolidatedData['warehouse_name'],
                'reference_number' => $generatedReferenceNumber,
                'delivery_date' => $consolidatedData['delivery_date'],
                'delivery_type' => $consolidatedData['delivery_type'],
                'created_by_name' => $createdByName,
                'created_by_id' => $createdById,
                'updated_by_id' => $createdById,
            ]);

            foreach ($consolidatedData['sessions'] as $storeOrders) {
                $storeCode = $storeOrders['store_code'];
                $storeName = $storeOrders['store_name'];
                $deliveryDate = $storeOrders['delivery_date'];
                $deliveryType = $storeOrders['delivery_type'];
                $orderDate = $storeOrders['order_date'];
                $storeSubUnitId = $storeOrders['store_sub_unit_id'];
                $storeSubUnitShortName = $storeOrders['store_sub_unit_short_name'];
                $storeSubUnitLongName = $storeOrders['store_sub_unit_long_name'];

                if (isset($storeOrders['ordered_items'])) {
                    foreach ($storeOrders['ordered_items'] as $orderedItems) {
                        $insertData[] = [
                            'store_receiving_inventory_id' => $storeReceivingInventory->id,
                            'is_special' => $orderedItems['is_special'] ?? false,
                            'reference_number' => 'CO-' . $storeOrders['order_session_id'],
                            'store_code' => $storeCode,
                            'store_name' => $storeName,
                            'delivery_date' => $deliveryDate,
                            'delivery_type' => $deliveryType,
                            'order_date' => $orderDate,
                            'item_code' => $orderedItems['item_code'],
                            'item_description' => $orderedItems['item_description'],
                            'order_quantity' => $orderedItems['order_quantity'],
                            'allocated_quantity' => $orderedItems['allocated_quantity'],
                            'store_sub_unit_id' => $storeSubUnitId,
                            'store_sub_unit_short_name' => $storeSubUnitShortName,
                            'store_sub_unit_long_name' => $storeSubUnitLongName,
                            'received_quantity' => 0,
                            'received_items' => json_encode([]),
                            'type' => 0, // Order
                            'created_by_id' => $createdById,
                            'created_by_name' => $createdByName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Bulk insert to speed up
            if (!empty($insertData)) {
                StoreReceivingInventoryItemModel::insert($insertData);
            }

            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            \Log::info($exception->getMessage());
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onGetById($store_receiving_inventory_id)
    {
        try {
            $storeReceivingInventoryModel = StoreReceivingInventoryModel::findOrFail($store_receiving_inventory_id);
            $data = [
                'reference_number' => $storeReceivingInventoryModel->reference_number,
                'store_inventory_items' => $storeReceivingInventoryModel->storeReceivingInventoryItems
            ];
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetCurrent($status, $store_code = null)
    {
        try {
            $storeReceivingInventoryItemModel = StoreReceivingInventoryItemModel::with('storeReceivingInventory')
                ->select([
                    'store_receiving_inventory_id',
                    'delivery_date',
                    'delivery_type',
                    DB::raw('COUNT(*) as total_items'),
                    'status',
                ])
                ->where('status', $status);

            if ($store_code !== null) {
                $storeReceivingInventoryItemModel->where('store_code', $store_code);
            }

            $storeReceivingInventoryItemModel = $storeReceivingInventoryItemModel->groupBy([
                'store_receiving_inventory_id',
                'delivery_date',
                'delivery_type',
                'status',
            ])->orderBy('store_receiving_inventory_id', 'DESC')->get();

            if (count($storeReceivingInventoryItemModel) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $storeReceivingInventoryItemModel);
            }

            return $this->dataResponse('error', 200, __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
