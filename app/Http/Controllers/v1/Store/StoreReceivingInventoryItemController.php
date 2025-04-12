<?php

namespace App\Http\Controllers\v1\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreReceivingInventoryItemCacheModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;
use App\Traits\ResponseTrait;
use DB;
class StoreReceivingInventoryItemController extends Controller
{
    use ResponseTrait;

    public function onGetCurrent($store_code, $status = null)
    {
        try {
            $storeInventoryItemModel = StoreReceivingInventoryItemModel::where('store_code', $store_code);
            if ($status != null) {
                $storeInventoryItemModel = $storeInventoryItemModel->where('status', $status);
            }
            $storeInventoryItemModel = $storeInventoryItemModel->orderBy('id', 'DESC')->get();

            return $this->dataResponse('success', 200, __('msg.record_found'), $storeInventoryItemModel);
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
            'scanned_items' => 'required|json', // {"bid":1,"item_code":"CR 12","q":1},{"bid":1,"item_code":"CR 12","q":1}
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $scannedItems = json_decode($fields['scanned_items'], true);
            $orderSessionId = $fields['order_session_id'];
            $createdById = $fields['created_by_id'];
            $wrongDroppedItems = [];
            $wrongDroppedData = [];
            $orderSessionData = [];
            foreach ($scannedItems as $items) {
                $itemCode = $items['item_code'];
                $storeInventoryItemModel = StoreReceivingInventoryItemModel::where('store_code', $store_code)
                    ->where('order_session_id', $orderSessionId)
                    ->where('item_code', $itemCode)
                    ->first();
                if ($storeInventoryItemModel) {
                    if (!isset($orderSessionData["$store_code-$orderSessionId-$itemCode"])) {
                        $orderSessionData["$store_code-$orderSessionId-$itemCode"] = [
                            'received_quantity' => 0,
                            'received_items' => []
                        ];
                    }
                    $orderSessionData["$store_code-$orderSessionId-$itemCode"]['received_quantity'] = ++$orderSessionData["$store_code-$orderSessionId-$itemCode"]['received_quantity'];
                    $orderSessionData["$store_code-$orderSessionId-$itemCode"]['received_items'][] = $items;
                } else {
                    $wrongDroppedItems[] = $items;
                }
            }

            foreach ($wrongDroppedItems as $items) {
                $itemCode = $items['item_code'];
                if (!isset($wrongDroppedData["$store_code-$orderSessionId-$itemCode"])) {
                    $wrongDroppedData["$store_code-$orderSessionId-$itemCode"] = [
                        'received_quantity' => 0,
                        'received_items' => []
                    ];
                }
                $wrongDroppedData["$store_code-$orderSessionId-$itemCode"]['received_quantity'] = ++$wrongDroppedData["$store_code-$orderSessionId-$itemCode"]['received_quantity'];
                $wrongDroppedData["$store_code-$orderSessionId-$itemCode"]['received_items'][] = $items;
            }
            $this->onUpdateOrderSessions($orderSessionData, $wrongDroppedData, $createdById, $orderSessionId);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    private function onUpdateOrderSessions($orderSessionData, $wrongDroppedData, $createdById, $orderSessionId)
    {
        try {
            foreach ($orderSessionData as $orderSessionKey => $orderSessionValue) {
                $key = explode('-', $orderSessionKey);
                $storeCode = $key[0];
                $itemCode = $key[2];

                $storeInventoryItemModel = StoreReceivingInventoryItemModel::where('store_code', $storeCode)
                    ->where('order_session_id', $orderSessionId)
                    ->where('item_code', $itemCode)
                    ->first();
                if ($storeInventoryItemModel) {
                    $storeInventoryItemModel->received_quantity = $orderSessionValue['received_quantity'];
                    $storeInventoryItemModel->received_items = json_encode($orderSessionValue['received_items']);
                    $storeInventoryItemModel->updated_by_id = $createdById;
                    $storeInventoryItemModel->save();
                }

            }

            $storeInventoryReceivingItem = StoreReceivingInventoryItemModel::where('order_session_id', $orderSessionId)->first();
            $storeReceivingInventoryId = $storeInventoryReceivingItem->store_receiving_inventory_id ?? null;
            $storeName = $storeInventoryReceivingItem->store_name ?? null;
            $deliveryType = $storeInventoryReceivingItem->delivery_type ?? null;
            $deliveryDate = $storeInventoryReceivingItem->delivery_date ?? null;
            $orderDate = $storeInventoryReceivingItem->order_date ?? null;
            foreach ($wrongDroppedData as $wrongDroppedKey => $wrongDroppedValue) {
                $key = explode('-', $wrongDroppedKey);
                $storeCode = $key[0];
                $itemCode = $key[2];

                $userModel = User::where('employee_id', $createdById)->first() ?? null;
                $firstName = $userModel->first_name ?? '';
                $lastName = $userModel->last_name ?? '';
                $storeInventoryItemModel = StoreReceivingInventoryItemModel::insert([
                    'store_receiving_inventory_id' => $storeReceivingInventoryId,
                    'is_special' => false,
                    'store_name' => $storeName,
                    'store_code' => $storeCode,
                    'order_session_id' => $orderSessionId,
                    'delivery_date' => $deliveryDate,
                    'delivery_type' => $deliveryType,
                    'order_date' => $orderDate,
                    'order_quantity' => 0,
                    'is_wrong_drop' => true,
                    'item_code' => $itemCode,
                    'received_quantity' => $wrongDroppedValue['received_quantity'],
                    'received_items' => json_encode($wrongDroppedValue['received_items']),
                    'created_by_id' => $createdById,
                    'created_by_name' => "$firstName $lastName",
                ]);
            }

            // Deletion of cache
            StoreReceivingInventoryItemCacheModel::where('order_session_id', $orderSessionId)->delete();
        } catch (Exception $exception) {
            throw new Exception('Error in updating order sessions');
        }
    }
}
