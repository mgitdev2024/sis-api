<?php

namespace App\Http\Controllers\v1\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreReceivingInventoryItemCacheModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Models\User;
use App\Traits\Stock\StockTrait;
use Http;
use Illuminate\Http\Request;
use Exception;
use App\Traits\ResponseTrait;
use DB;
class StoreReceivingInventoryItemController extends Controller
{
    use ResponseTrait, StockTrait;

    public function onGetCurrent($store_code, $status = null, $order_session_id = null)
    {
        try {
            $storeInventoryItemModel = StoreReceivingInventoryItemModel::where('store_code', $store_code);
            if ($status != null) {
                $storeInventoryItemModel = $storeInventoryItemModel->where('status', $status);
            }
            if ($order_session_id != null) {
                $storeInventoryItemModel = $storeInventoryItemModel->where('order_session_id', $order_session_id);
            }
            $storeInventoryItemModel = $storeInventoryItemModel->orderBy('id', 'DESC')->get();

            $data = [
                'reservation_request' => [],
                'requested_items' => [],
                'request_details' => [],
            ];
            foreach ($storeInventoryItemModel as $item) {
                $data['reservation_request'] = [
                    'delivery_location' => $item->store_name,
                    'estimated_delivery_date' => $item->delivery_date,
                    'reference_number' => $order_session_id
                ];

                $data['requested_items'][] = [
                    'reference_number' => $order_session_id,
                    'item_code' => trim($item->item_code),
                    'order_quantity' => $item->order_quantity,
                    'allocated_quantity' => $item->allocated_quantity,
                    'received_quantity' => $item->received_quantity,
                    'received_items' => json_decode($item->received_items),
                    'is_special' => $item->is_special,
                    'is_wrong_drop' => $item->is_wrong_drop,
                    'created_by_name' => $item->created_by_name,
                    'status' => $item->status,
                ];

                $data['request_details'] = [
                    'supply_hub' => $item->storeReceivingInventory->warehouse_name,
                    'delivery_location' => $item->delivery_date,
                    'delivery_scheme' => $item->delivery_type,
                    'requested_by' => $item->created_by_name,
                    'status' => $item->status,
                ];
            }

            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetCategory($store_code, $status = null)
    {
        try {
            $storeInventoryItemModel = StoreReceivingInventoryItemModel::select([
                'order_session_id',
                'delivery_date',
                'status',
                DB::raw('COUNT(order_session_id) as session_count'),
            ])
                ->where('store_code', $store_code);
            if ($status != null) {
                $storeInventoryItemModel = $storeInventoryItemModel->where('status', $status);
            }
            $storeInventoryItemModel = $storeInventoryItemModel->groupBy([
                'order_session_id',
                'delivery_date',
                'status'
            ])
                ->orderBy('delivery_date', 'DESC')
                ->get();

            return $this->dataResponse('success', 200, __('msg.record_found'), $storeInventoryItemModel);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onScanItems(Request $request, $store_code)
    {
        $fields = $request->validate([
            'order_session_id' => 'required',
            'scanned_items' => 'required|json', // ["2":{"bid":1,"q":1,"item_code":"TAS WH"},"3":{"bid":1,"q":1}]
            'created_by_id' => 'required',
            'receive_type' => 'required|in:scan,manual'
        ]);
        try {
            $receiveType = $fields['receive_type']; // Stock In, Stock Out
            DB::beginTransaction();
            $scannedItems = json_decode($fields['scanned_items'], true);
            $orderSessionId = $fields['order_session_id'];
            $createdById = $fields['created_by_id'];
            $wrongDroppedItems = [];
            $wrongDroppedData = [];
            $orderSessionData = [];
            foreach ($scannedItems as $items) {

                $itemCode = $items['ic']; // item code
                $storeInventoryItemModel = StoreReceivingInventoryItemModel::where('store_code', $store_code)
                    ->where('order_session_id', $orderSessionId)
                    ->where('item_code', $itemCode)
                    ->first();
                if ($storeInventoryItemModel) {
                    if (!isset($orderSessionData["$store_code:$orderSessionId:$itemCode"])) {
                        $orderSessionData["$store_code:$orderSessionId:$itemCode"] = [
                            'received_quantity' => 0,
                            'received_items' => []
                        ];
                    }

                    $receivedQuantity = 0;
                    if ($receiveType == 'scan') {
                        $receivedQuantity = ++$orderSessionData["$store_code:$orderSessionId:$itemCode"]['received_quantity'];

                    } else {
                        $receivedQuantity += $items['q'] ?? 0;
                    }
                    $orderSessionData["$store_code:$orderSessionId:$itemCode"]['received_quantity'] = $receivedQuantity;
                    $orderSessionData["$store_code:$orderSessionId:$itemCode"]['received_items'][] = $items;

                } else {
                    $wrongDroppedItems[] = $items;
                }
            }

            foreach ($wrongDroppedItems as $items) {
                $itemCode = $items['ic']; // item code
                if (!isset($wrongDroppedData["$store_code:$orderSessionId:$itemCode"])) {
                    $wrongDroppedData["$store_code:$orderSessionId:$itemCode"] = [
                        'received_quantity' => 0,
                        'received_items' => []
                    ];
                }
                $wrongQuantity = 0;
                if ($receiveType == 'scan') {
                    $wrongQuantity = ++$wrongDroppedData["$store_code:$orderSessionId:$itemCode"]['received_quantity'];

                } else {
                    $wrongQuantity += $items['q'] ?? 0;
                }
                $wrongDroppedData["$store_code:$orderSessionId:$itemCode"]['received_quantity'] = $wrongQuantity;
                $wrongDroppedData["$store_code:$orderSessionId:$itemCode"]['received_items'][] = $items;

            }

            $this->onUpdateOrderSessions($orderSessionData, $wrongDroppedData, $createdById, $orderSessionId, $receiveType);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    private function onUpdateOrderSessions($orderSessionData, $wrongDroppedData, $createdById, $orderSessionId, $receiveType)
    {
        try {

            foreach ($orderSessionData as $orderSessionKey => $orderSessionValue) {
                $key = explode(':', $orderSessionKey);
                $storeCode = $key[0];
                $referenceNumber = $key[1];
                $itemCode = $key[2];

                $storeInventoryItemModel = StoreReceivingInventoryItemModel::where('store_code', $storeCode)
                    ->where('order_session_id', $orderSessionId)
                    ->where('item_code', $itemCode)
                    ->first();
                if ($storeInventoryItemModel) {
                    $storeInventoryItemModel->received_quantity = $orderSessionValue['received_quantity'];
                    $storeInventoryItemModel->received_items = json_encode($orderSessionValue['received_items'] ?? []);
                    $storeInventoryItemModel->receive_type = $receiveType == 'scan' ? 0 : 1;
                    $storeInventoryItemModel->updated_by_id = $createdById;
                    $storeInventoryItemModel->updated_at = now();
                    $storeInventoryItemModel->status = 1;
                    $storeInventoryItemModel->save();

                    // Stock In
                    $storeSubUnitShortName = $storeInventoryItemModel->store_sub_unit_short_name;
                    $storeInventoryItemId = $storeInventoryItemModel->id;
                    $this->onCreateStockLogs('stock_in', $storeCode, $storeSubUnitShortName, $createdById, $receiveType, $storeInventoryItemId, $orderSessionValue['received_items'], $referenceNumber);
                }

            }

            $storeInventoryReceivingItem = StoreReceivingInventoryItemModel::where('order_session_id', $orderSessionId)->first();
            $storeReceivingInventoryId = $storeInventoryReceivingItem->store_receiving_inventory_id ?? null;
            $storeName = $storeInventoryReceivingItem->store_name ?? null;
            $deliveryType = $storeInventoryReceivingItem->delivery_type ?? null;
            $deliveryDate = $storeInventoryReceivingItem->delivery_date ?? null;
            $orderDate = $storeInventoryReceivingItem->order_date ?? null;
            $storeSubUnitId = $storeInventoryReceivingItem->store_sub_unit_id ?? null;
            $storeSubUnitShortName = $storeInventoryReceivingItem->store_sub_unit_short_name ?? null;
            $storeSubUnitLongName = $storeInventoryReceivingItem->store_sub_unit_long_name ?? null;

            foreach ($wrongDroppedData as $wrongDroppedKey => $wrongDroppedValue) {
                $key = explode(':', $wrongDroppedKey);
                $storeCode = $key[0];
                $referenceNumber = $key[1];
                $itemCode = $key[2];

                $response = Http::get(env('MGIOS_URL') . '/check-item-code/' . $itemCode);
                if ($response->failed()) {
                    continue; // throw new Exception if this is not valid
                }
                $itemData = $response->json();
                $userModel = User::where('employee_id', $createdById)->first() ?? null;
                $firstName = $userModel->first_name ?? '';
                $lastName = $userModel->last_name ?? '';
                $storeInventoryItemModel = StoreReceivingInventoryItemModel::create([
                    'store_receiving_inventory_id' => $storeReceivingInventoryId,
                    'is_special' => false,
                    'store_name' => $storeName,
                    'store_code' => $storeCode,
                    'order_session_id' => $orderSessionId,
                    'delivery_date' => $deliveryDate,
                    'delivery_type' => $deliveryType,
                    'order_date' => $orderDate,
                    'order_quantity' => 0,
                    'allocated_quantity' => 0,
                    'store_sub_unit_id' => $storeSubUnitId,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'store_sub_unit_long_name' => $storeSubUnitLongName,
                    'is_wrong_drop' => true,
                    'item_code' => trim($itemCode),
                    'item_description' => $itemData['long_name'], // API to be called for Item Masterdata long name
                    'received_quantity' => $wrongDroppedValue['received_quantity'],
                    'received_items' => json_encode($wrongDroppedValue['received_items'] ?? []),
                    'created_by_id' => $createdById,
                    'created_by_name' => "$firstName $lastName",
                    'status' => 1,
                ]);
            }

            $this->onCreateStockLogs('stock_in', $storeCode, $storeSubUnitShortName, $createdById, $receiveType, $storeInventoryItemModel->id, $wrongDroppedValue['received_items'], $referenceNumber);

            // Deletion of cache
            $cacheQuery = StoreReceivingInventoryItemCacheModel::where('order_session_id', $orderSessionId);

            if ($cacheQuery->exists()) {
                $cacheQuery->delete();
            }

        } catch (Exception $exception) {
            throw new Exception('Error in updating order sessions');
        }
    }
}
