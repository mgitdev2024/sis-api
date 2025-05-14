<?php

namespace App\Http\Controllers\v1\Store;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockLogModel;
use App\Models\Stock\StockTransferItemModel;
use App\Models\Stock\StockTransferModel;
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

    public function onGetCurrent($store_code, $order_type, $is_received, $status = null, $reference_number = null)
    {
        try {
            $storeInventoryItemModel = StoreReceivingInventoryItemModel::where([
                'store_code' => $store_code,
                'order_type' => $order_type,
            ]);
            if ($is_received != 'all') {
                $storeInventoryItemModel = $storeInventoryItemModel->where('is_received', $is_received);
            }
            if ($status != null) {
                $storeInventoryItemModel = $storeInventoryItemModel->where('status', $status);
            }
            if ($reference_number != null) {
                $storeInventoryItemModel = $storeInventoryItemModel->where('reference_number', $reference_number);
            }
            $storeInventoryItemModel = $storeInventoryItemModel->orderBy('id', 'DESC')->get();

            $data = [
                'reservation_request' => [],
                'requested_items' => [],
                'request_details' => [],
            ];

            $isReceived = true;
            $counter = 0;
            foreach ($storeInventoryItemModel as $item) {
                $itemCode = trim($item->item_code);
                $orderType = $item->order_type;
                $fanOutCategory = $item->fan_out_category;
                $uniqueKey = "$itemCode:$fanOutCategory";
                if ($item->is_received == 0) {
                    $isReceived = false;
                } else {
                    $uniqueKey .= "-$fanOutCategory";
                }

                $data['reservation_request'] = [
                    'delivery_location' => $item->store_name,
                    'estimated_delivery_date' => $item->delivery_date,
                    'reference_number' => $reference_number
                ];

                $data['requested_items'][] = [
                    'unique_key' => "$uniqueKey",
                    'reference_number' => $reference_number,
                    'item_code' => $itemCode,
                    'item_description' => $item->item_description,
                    'order_quantity' => $item->order_quantity,
                    'allocated_quantity' => $item->allocated_quantity,
                    'received_quantity' => $item->received_quantity,
                    'received_items' => json_decode($item->received_items),
                    'order_type' => $orderType,
                    'fan_out_category' => $fanOutCategory,
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
                $data['request_details']['additional_info'] = $this->onCheckReferenceNumber($reference_number);
                $counter++;
            }

            $data['reservation_request']['is_received'] = $isReceived;
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    private function onCheckReferenceNumber($referenceNumber)
    {
        $referenceExplode = explode('-', $referenceNumber);
        $referenceKey = $referenceExplode[0] ?? null;
        $referenceNumberCollection = [
            // 'CO',
            'ST' => StockTransferModel::class
        ];

        $model = null;

        if (array_key_exists($referenceKey, $referenceNumberCollection)) {
            $model = $referenceNumberCollection[$referenceKey]::where('reference_number', $referenceNumber)->first();
        }

        return $model;
    }

    public function onGetCheckedManual($reference_number, $order_type, $selected_item_codes)
    {
        try {
            $itemCodes = json_decode($selected_item_codes, true);
            $storeInventoryItemArr = [];
            foreach ($itemCodes as $selectedItemCode) {
                $explodedItemCodes = explode(':', $selectedItemCode);
                $itemCode = $explodedItemCodes[0];
                $fanOutCategory = $explodedItemCodes[1] ?? null;
                $storeInventoryItemModel = StoreReceivingInventoryItemModel::where([
                    'reference_number' => $reference_number,
                    'order_type' => $order_type,
                    'item_code' => $itemCode
                ]);

                if ($fanOutCategory != null) {
                    $storeInventoryItemModel->where('fan_out_category', $fanOutCategory);
                }
                $storeInventoryItemModel = $storeInventoryItemModel->first();
                $storeInventoryItemArr[] = $storeInventoryItemModel;
            }

            $data = [
                'reservation_request' => [],
                'requested_items' => [],
                'request_details' => [],
            ];

            $isReceived = true;

            $counter = 0;
            foreach ($storeInventoryItemArr as $item) {
                $itemCode = trim($item->item_code);
                $orderType = $item->order_type;
                $fanOutCategory = $item->fan_out_category;
                $uniqueKey = "$itemCode:$fanOutCategory";

                if ($item->is_received == 0) {
                    $isReceived = false;
                } else {
                    $uniqueKey .= "-$fanOutCategory";
                }


                $data['reservation_request'] = [
                    'delivery_location' => $item->store_name,
                    'estimated_delivery_date' => $item->delivery_date,
                    'reference_number' => $reference_number
                ];

                $data['requested_items'][] = [
                    'unique_key' => "$uniqueKey",
                    'reference_number' => $reference_number,
                    'item_code' => $itemCode,
                    'item_description' => $item->item_description,
                    'order_quantity' => $item->order_quantity,
                    'fan_out_category' => $fanOutCategory,
                    'allocated_quantity' => $item->allocated_quantity,
                    'received_quantity' => $item->received_quantity,
                    'received_items' => json_decode($item->received_items),
                    'order_type' => $orderType,
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

                $counter++;
            }

            $data['reservation_request']['is_received'] = $isReceived;
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetCategory($store_code, $status = null)
    {
        try {
            $storeInventoryItemModel = StoreReceivingInventoryItemModel::select([
                'reference_number',
                'delivery_date',
                'status',
                DB::raw('MAX(type) as type'),
                DB::raw('COUNT(reference_number) as session_count'),
            ])
                ->where('store_code', $store_code);
            if ($status != null) {
                $storeInventoryItemModel = $storeInventoryItemModel->where('status', $status);
            }
            $storeInventoryItemModel = $storeInventoryItemModel->groupBy([
                'reference_number',
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
            'reference_number' => 'required',
            'scanned_items' => 'required|json', // ["2":{"bid":1,"q":1,"item_code":"TAS WH"},"3":{"bid":1,"q":1}]
            'created_by_id' => 'required',
            'receive_type' => 'required|in:scan,manual',
            'order_type' => 'nullable|in:0,1,2', // 0 = Order, 1 = Manual, 2 = Fan Out Category
        ]);
        try {
            $receiveType = $fields['receive_type']; // Stock In, Stock Out
            DB::beginTransaction();
            $scannedItems = json_decode($fields['scanned_items'], true);
            $referenceNumber = $fields['reference_number'];
            $createdById = $fields['created_by_id'];
            $orderType = $fields['order_type'] ?? null;
            $wrongDroppedItems = [];
            $wrongDroppedData = [];
            $orderSessionData = [];
            foreach ($scannedItems as $items) {

                $itemCode = $items['ic']; // item code
                $fanOutCategory = $items['foc'] ?? '>';
                $storeInventoryItemModel = StoreReceivingInventoryItemModel::where([
                    'store_code' => $store_code,
                    'reference_number' => $referenceNumber,
                    'item_code' => $itemCode
                ]);
                if ($orderType != null) {
                    $storeInventoryItemModel->where('order_type', $orderType);
                }
                if ($fanOutCategory != '>') {
                    $storeInventoryItemModel->where('fan_out_category', $fanOutCategory);
                }
                $storeInventoryItemModel = $storeInventoryItemModel->first();
                if ($storeInventoryItemModel) {
                    if (!isset($orderSessionData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"])) {
                        $orderSessionData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"] = [
                            'received_quantity' => 0,
                            'received_items' => []
                        ];

                        if ($orderType != null) {
                            $orderSessionData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"]['order_type'] = $storeInventoryItemModel->order_type;
                        }
                    }

                    $receivedQuantity = 0;
                    if ($receiveType == 'scan') {
                        $receivedQuantity = ++$orderSessionData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"]['received_quantity'];

                    } else {
                        $receivedQuantity += $items['q'] ?? 0;
                    }
                    $orderSessionData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"]['received_quantity'] = $receivedQuantity;
                    $items['fan_out_category'] = $storeInventoryItemModel->fan_out_category;
                    $orderSessionData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"]['received_items'][] = $items;
                } else {
                    $wrongDroppedItems[] = $items;
                }
            }

            foreach ($wrongDroppedItems as $items) {
                $itemCode = $items['ic']; // item code
                if (!isset($wrongDroppedData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"])) {
                    $wrongDroppedData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"] = [
                        'received_quantity' => 0,
                        'received_items' => []
                    ];

                    if ($orderType != null) {
                        $wrongDroppedData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"]['order_type'] = $orderType;
                    }
                }
                $wrongQuantity = 0;
                if ($receiveType == 'scan') {
                    $wrongQuantity = ++$wrongDroppedData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"]['received_quantity'];

                } else {
                    $wrongQuantity += $items['q'] ?? 0;
                }
                $wrongDroppedData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"]['received_quantity'] = $wrongQuantity;
                $wrongDroppedData["$store_code:$referenceNumber:$itemCode:$fanOutCategory"]['received_items'][] = $items;

            }

            $this->onUpdateOrderSessions($orderSessionData, $wrongDroppedData, $createdById, $referenceNumber, $receiveType);
            DB::commit();

            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    private function onUpdateOrderSessions($orderSessionData, $wrongDroppedData, $createdById, $referenceNumber, $receiveType)
    {
        try {
            foreach ($orderSessionData as $orderSessionKey => $orderSessionValue) {
                $key = explode(':', $orderSessionKey);
                $storeCode = $key[0];
                $referenceNumber = $key[1];
                $itemCode = $key[2];
                $fanOutCategory = $key[3] != '>' ? $key[3] : null;
                $orderType = $orderSessionValue['order_type'] ?? null;
                $storeInventoryItemModel = StoreReceivingInventoryItemModel::where([
                    'store_code' => $storeCode,
                    'reference_number' => $referenceNumber,
                    'item_code' => $itemCode
                ]);

                if ($orderType != null) {
                    $storeInventoryItemModel->where('order_type', $orderType);
                }
                if ($fanOutCategory != null) {
                    $storeInventoryItemModel->where('fan_out_category', $fanOutCategory);
                }
                $storeInventoryItemModel = $storeInventoryItemModel->first();
                if ($storeInventoryItemModel) {
                    $storeInventoryItemModel->received_quantity = $orderSessionValue['received_quantity'];
                    $storeInventoryItemModel->received_items = json_encode($orderSessionValue['received_items'] ?? []);
                    $storeInventoryItemModel->receive_type = $receiveType == 'scan' ? 0 : 1;
                    $storeInventoryItemModel->updated_by_id = $createdById;
                    $storeInventoryItemModel->updated_at = now();
                    $storeInventoryItemModel->status = 0;
                    $storeInventoryItemModel->is_received = 1;
                    $storeInventoryItemModel->save();

                    // Stock In
                    $storeSubUnitShortName = $storeInventoryItemModel->store_sub_unit_short_name;
                    $storeInventoryItemId = $storeInventoryItemModel->id;
                    $itemDescription = $storeInventoryItemModel->item_description;
                    $itemCategoryName = $storeInventoryItemModel->item_category_name;
                    $this->onCreateStockLogs('stock_in', $storeCode, $storeSubUnitShortName, $createdById, $receiveType, $storeInventoryItemId, $orderSessionValue['received_items'], $referenceNumber, $itemDescription, $itemCategoryName);
                }

            }

            $storeInventoryReceivingItem = StoreReceivingInventoryItemModel::where('reference_number', $referenceNumber)->first();
            $storeReceivingInventoryId = $storeInventoryReceivingItem->store_receiving_inventory_id ?? null;
            $storeName = $storeInventoryReceivingItem->store_name ?? null;
            $deliveryType = $storeInventoryReceivingItem->delivery_type ?? null;
            $deliveryDate = $storeInventoryReceivingItem->delivery_date ?? null;
            $orderDate = $storeInventoryReceivingItem->order_date ?? null;
            // $storeSubUnitId = $storeInventoryReceivingItem->store_sub_unit_id ?? null;
            $storeSubUnitShortName = $storeInventoryReceivingItem->store_sub_unit_short_name ?? null;
            $storeSubUnitLongName = $storeInventoryReceivingItem->store_sub_unit_long_name ?? null;


            foreach ($wrongDroppedData as $wrongDroppedKey => $wrongDroppedValue) {
                $key = explode(':', $wrongDroppedKey);
                $storeCode = $key[0];
                $referenceNumber = $key[1];
                $itemCode = $key[2];

                $response = Http::get(env('MGIOS_URL') . '/check-item-code/' . $itemCode);
                if ($response->failed()) {
                    throw new Exception('Error in API call');
                    // throw new Exception if this is not valid
                }
                $itemData = $response->json();
                $userModel = User::where('employee_id', $createdById)->first() ?? null;
                $firstName = $userModel->first_name ?? '';
                $lastName = $userModel->last_name ?? '';
                $storeInventoryItemModel = StoreReceivingInventoryItemModel::create([
                    'store_receiving_inventory_id' => $storeReceivingInventoryId,
                    'order_type' => 0,
                    'store_name' => $storeName,
                    'store_code' => $storeCode,
                    'reference_number' => $referenceNumber,
                    'delivery_date' => $deliveryDate,
                    'delivery_type' => $deliveryType,
                    'receive_type' => 1,
                    'type' => 0, // Order
                    'order_date' => $orderDate,
                    'order_quantity' => 0,
                    'allocated_quantity' => 0,
                    // 'store_sub_unit_id' => $storeSubUnitId,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'store_sub_unit_long_name' => $storeSubUnitLongName,
                    'is_wrong_drop' => true,
                    'item_code' => trim($itemCode),
                    'item_description' => $itemData['long_name'], // API to be called for Item Masterdata long name
                    'item_category_name' => $itemData['item_base']['item_category']['category_name'] ?? null,
                    'received_quantity' => $wrongDroppedValue['received_quantity'],
                    'received_items' => json_encode($wrongDroppedValue['received_items'] ?? []),
                    'is_received' => 1,
                    'created_by_id' => $createdById,
                    'created_by_name' => "$firstName $lastName",
                    'status' => 0,
                ]);
                $this->onCreateStockLogs('stock_in', $storeCode, $storeSubUnitShortName, $createdById, $receiveType, $storeInventoryItemModel->id, $wrongDroppedValue['received_items'], $referenceNumber, $itemData['long_name'], $itemData['item_base']['item_category']['category_name']);
            }


            // Deletion of cache
            $cacheQuery = StoreReceivingInventoryItemCacheModel::where('reference_number', $referenceNumber);

            if ($cacheQuery->exists()) {
                $cacheQuery->delete();
            }

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onComplete(Request $request, $reference_number)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $storeInventoryItemModel = StoreReceivingInventoryItemModel::where('reference_number', $reference_number)->get();
            if (count($storeInventoryItemModel) > 0) {
                foreach ($storeInventoryItemModel as $item) {
                    $item->status = 1;
                    $item->updated_by_id = $fields['created_by_id'];
                    $item->updated_at = now();
                    $item->save();
                }

                $this->onCheckReferenceNumberCompletion($reference_number);
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'));
            }
            return $this->dataResponse('error', 404, __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    private function onCheckReferenceNumberCompletion($referenceNumber)
    {
        $referenceExplode = explode('-', $referenceNumber);
        $referenceKey = $referenceExplode[0];
        $referenceNumberCollection = [
            'ST' => StockTransferModel::class
        ];

        if (array_key_exists($referenceKey, $referenceNumberCollection)) {
            $referenceNumberCollection[$referenceKey]::where('reference_number', $referenceNumber)->update([
                'status' => 1
            ]);
        }
    }
}
