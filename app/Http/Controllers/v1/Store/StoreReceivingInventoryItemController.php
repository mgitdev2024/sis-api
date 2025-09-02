<?php

namespace App\Http\Controllers\v1\Store;

use App\Http\Controllers\Controller;
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
use Carbon\Carbon;
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
            $storeInventoryItemModel = $storeInventoryItemModel->orderBy('item_category_name', 'ASC')->get();

            $data = [
                'reservation_request' => [],
                'requested_items' => [],
                'request_details' => [],
            ];

            $isReceived = true;
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

                if (count($data['reservation_request']) === 0) {
                    $data['reservation_request'] = [
                        'delivery_location' => $item->store_name,
                        'estimated_delivery_date' => Carbon::parse($item->delivery_date)->format('F d, Y'),
                        'reference_number' => $reference_number,
                        'order_session_id' => $item->order_session_id ?? null,
                    ];

                    $data['reservation_request']['is_received'] = $isReceived;
                }

                if (count($data['request_details']) === 0) {
                    $data['request_details'] = [
                        'supply_hub' => $item->storeReceivingInventory->warehouse_name,
                        'delivery_location' => Carbon::parse($item->delivery_date)->format('F d, Y'),
                        'delivery_scheme' => $item->delivery_type,
                        'order_date' => Carbon::parse($item->order_date)->format('F d, Y'),
                        'requested_by' => $item->created_by_name,
                        'completed_by' => $item->completed_by_label ?? null,
                        'completed_at' => $item->completed_at != null ? Carbon::parse($item->completed_at)->format('F d, Y') : null,
                        'status' => $item->status,
                    ];

                    $data['request_details']['additional_info'] = $this->onCheckReferenceNumber($reference_number);
                }

                $data['requested_items'][] = [
                    'unique_key' => "$uniqueKey",
                    'reference_number' => $reference_number,
                    'id' => $item->id,
                    'item_code' => $itemCode,
                    'item_description' => $item->item_description,
                    'item_category_name' => $item->item_category_name,
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
            }

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

                if (count($data['reservation_request']) === 0) {
                    $data['reservation_request'] = [
                        'delivery_location' => $item->store_name,
                        'estimated_delivery_date' => Carbon::parse($item->delivery_date)->format('F d, Y'),
                        'reference_number' => $reference_number,
                        'order_session_id' => $item->order_session_id ?? null,
                    ];
                    $data['reservation_request']['is_received'] = $isReceived;

                }
                if (count($data['request_details']) === 0) {
                    $data['request_details'] = [
                        'supply_hub' => $item->storeReceivingInventory->warehouse_name,
                        'delivery_location' => Carbon::parse($item->delivery_date)->format('F d, Y'),
                        'delivery_scheme' => $item->delivery_type,
                        'order_date' => Carbon::parse($item->order_date)->format('F d, Y'),
                        'requested_by' => $item->created_by_name,
                        'completed_by' => $item->completed_by_name ?? null,
                        'completed_at' => $item->completed_at != null ? Carbon::parse($item->completed_at)->format('F d, Y') : null,
                        'status' => $item->status,
                    ];
                    $data['request_details']['additional_info'] = $this->onCheckReferenceNumber($reference_number);
                }

                $data['requested_items'][] = [
                    'unique_key' => "$uniqueKey",
                    'reference_number' => $reference_number,
                    'item_code' => $itemCode,
                    'item_description' => $item->item_description,
                    'item_category_name' => $item->item_category_name,
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
            }

            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetCategory($store_code, $status = null, $back_date = null, $sub_unit = null)
    {
        try {
            $storeInventoryItemModel = StoreReceivingInventoryItemModel::from('store_receiving_inventory_items as srt')->select([
                'srt.reference_number',
                'srt.order_session_id',
                'srt.delivery_date',
                'sri.delivery_type',
                'sri.warehouse_name',
                'srt.status',
                DB::raw('MAX(srt.type) as type'),
                DB::raw('COUNT(srt.reference_number) as session_count'),
            ])
                ->leftJoin('store_receiving_inventory as sri', 'srt.store_receiving_inventory_id', '=', 'sri.id')
                ->where('store_code', $store_code);
            if ($status != null) {
                $storeInventoryItemModel = $storeInventoryItemModel->where('srt.status', $status);
            }
            if ($back_date != 'undefined') {
                $storeInventoryItemModel = $storeInventoryItemModel->whereDate('srt.delivery_date', $back_date);
            } else {
                $storeInventoryItemModel = $storeInventoryItemModel->whereDate('srt.delivery_date', now());
            }
            if ($sub_unit != null) {
                $storeInventoryItemModel = $storeInventoryItemModel->where('srt.store_sub_unit_short_name', $sub_unit);
            }
            $storeInventoryItemModel = $storeInventoryItemModel->groupBy([
                'srt.reference_number',
                'srt.order_session_id',
                'srt.delivery_date',
                'sri.delivery_type',
                'sri.warehouse_name',
                'srt.status',
            ])
                ->orderBy('srt.delivery_date', 'DESC')
                ->get()->map(function ($item) {
                    $item->delivery_date = Carbon::parse($item->delivery_date)->format('F d, Y');
                    $item->setAppends(array_diff($item->getAppends(), ['received_by_label', 'received_at_label']));
                    return $item;
                });

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
            'order_type' => 'nullable|in:0,1,2', // 0 = Order, 1 = Advance, 2 = Fan Out Category
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
                if ($orderType !== null) {
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
                        //
                        if ($orderType !== null) {
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

                    if ($orderType !== null) {
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

                if ($orderType !== null) {
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
                    $storeInventoryItemModel->received_at = now();
                    $storeInventoryItemModel->received_by_id = $createdById;
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
            $orderSessionId = $storeInventoryReceivingItem->order_session_id ?? null;

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
                    'order_session_id' => $orderSessionId,
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
                    'received_at' => now(),
                    'received_by_id' => $createdById,
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
                $createdById = $fields['created_by_id'];
                foreach ($storeInventoryItemModel as $item) {
                    $item->status = 1;
                    $item->updated_by_id = $createdById;
                    $item->updated_at = now();
                    $item->completed_by_id = $createdById;
                    $item->completed_at = now();
                    if ($item->received_at === null) {
                        $item->received_at = now();
                        $item->received_by_id = $createdById;
                    }
                    $item->save();
                }

                $this->onCheckReferenceNumberCompletion($reference_number, $createdById);
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'));
            }
            return $this->dataResponse('error', 404, __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    private function onCheckReferenceNumberCompletion($referenceNumber, $createdById)
    {
        $referenceExplode = explode('-', $referenceNumber);
        $referenceKey = $referenceExplode[0];
        $referenceNumberCollection = [
            'ST' => [
                'model' => StockTransferModel::class,
                'status' => 2 // 1 = For Receive, 1.1 = In warehouse, 2 = Received
            ],
            'SWS' => [
                'model' => StockTransferModel::class,
                'status' => 2 // 1 = For Receive, 1.1 = In warehouse, 2 = Received
            ]
        ];

        if (array_key_exists($referenceKey, $referenceNumberCollection)) {
            $referenceNumberCollection[$referenceKey]['model']::where('reference_number', $referenceNumber)->update([
                'status' => $referenceNumberCollection[$referenceKey]['status'],
                'store_received_by_id' => $createdById,
                'store_received_at' => now(),
            ]);
        }
    }

    // ------------- Added New Function

    public function onAddRemarks(Request $request, $reference_number)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'store_inventory_item_data' => 'required' // [{"id":1,"re":"Naiwan"},{"id":2,"re":"Nahulog"}]
        ]);
        try {
            DB::beginTransaction();

            $storeInventoryItemData = json_decode($fields['store_inventory_item_data'], true);
            foreach ($storeInventoryItemData as $item) {
                $storeInventoryItem = StoreReceivingInventoryItemModel::find($item['id']);
                if ($storeInventoryItem) {
                    $storeInventoryItem->remarks = trim($item['re'] ?? '') ?: null;
                    $storeInventoryItem->save();
                }
            }

            $onCompleteRequestForm = new Request([
                'created_by_id' => $fields['created_by_id']
            ]);
            $this->onComplete($onCompleteRequestForm, $reference_number);

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 404, __('msg.update_failed'));
        }
    }

    public function onGetCountOrderType($store_code, $reference_number, $sub_unit = null)
    {
        try {
            $storeInventoryItemModel = StoreReceivingInventoryItemModel::toBase()
                ->selectRaw('order_type, COUNT(*) as count')
                ->where([
                    'store_code' => $store_code,
                    'reference_number' => $reference_number
                ]);
            if ($sub_unit) {
                $storeInventoryItemModel->where('store_sub_unit_short_name', $sub_unit);
            }
            $storeInventoryItemModel = $storeInventoryItemModel
                ->groupBy('order_type')
                ->get();
            return $this->dataResponse('success', 200, __('msg.record_found'), $storeInventoryItemModel);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'));
        }
    }
}
