<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;

class StoreReceivingReportController extends Controller
{
    use ResponseTrait;
    public function onGenerateDeliveryReceivingReport(Request $request)
    {
        try {
            $storeCode = $request->store_code ?? null;
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;
            $deliveryDateRange = $request->delivery_date_range ?? null; // Expected format: 'YYYY-MM-DD to YYYY-MM-DD'
            $deliveryDateExplode = $deliveryDateRange != null ? explode('to', str_replace(' ', '', $deliveryDateRange)) : null;
            $deliveryDateFrom = isset($deliveryDateExplode[0]) ? date('Y-m-d', strtotime($deliveryDateExplode[0])) : null;
            $deliveryDateTo = isset($deliveryDateExplode[1]) ? date('Y-m-d', strtotime($deliveryDateExplode[1])) : null;
            $status = $request->status ?? null; // Expected values: 0 (Pending), 1 (Complete)

            $storeReceivingInventoryItems = StoreReceivingInventoryItemModel::select([
                'store_code',
                'store_name',
                'store_sub_unit_short_name',
                'order_session_id',
                'delivery_date',
                'item_code',
                'order_type',
                'type',
                'item_description',
                'item_category_name',
                'order_quantity',
                'allocated_quantity',
                'received_quantity',
                'receive_type',
                'completed_by_id',
                'completed_at',
                'status'
            ]);
            if ($storeCode) {
                $storeReceivingInventoryItems->where('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $storeReceivingInventoryItems->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($deliveryDateFrom && $deliveryDateTo) {
                $storeReceivingInventoryItems->whereBetween('delivery_date', [$deliveryDateFrom, $deliveryDateTo]);
            } else if ($deliveryDateFrom) {
                $storeReceivingInventoryItems->whereDate('delivery_date', $deliveryDateFrom);
            }
            if ($status) {
                $storeReceivingInventoryItems->where('status', $status);
            }
            $storeReceivingInventoryItems = $storeReceivingInventoryItems->orderBy('order_quantity', 'DESC')->get();

            $reportData = [];
            foreach ($storeReceivingInventoryItems as $item) {
                $storeCode = $item['store_code'] ?? null;
                $storeName = $item['store_name'] ?? null;
                $storeSubUnitShortName = $item['store_sub_unit_short_name'] ?? null;
                $orderType = $item['order_type_label'] ?? null;
                $orderSessionId = $item['order_session_id'] ?? null;
                $itemCode = $item['item_code'] ?? null;
                $itemDescription = $item['item_description'] ?? null;
                $itemCategoryName = $item['item_category_name'] ?? null;
                $orderQuantity = $item['order_quantity'] ?? null;
                $allocatedQuantity = $item['allocated_quantity'] ?? null;
                $receivedQuantity = $item['received_quantity'] ?? null;
                $receiveType = $item['receive_type_label'] ?? null;
                $completedById = $item['completed_by_label'] ?? null;
                $completedAt = $item['completed_at_label'] ?? null;
                $deliveryDate = $item['delivery_date_label'] ?? null;
                $remarks = null;
                $variance = intval($receivedQuantity) - intval($allocatedQuantity);

                if ($receivedQuantity < $allocatedQuantity) {
                    $remarks = 'Short';
                } else if ($receivedQuantity > $allocatedQuantity) {
                    $remarks = 'Over';
                } else {
                    $remarks = 'Completed';
                }

                $reportData[] = [
                    'dr_no' => $orderSessionId,
                    'delivery_date' => $deliveryDate,
                    'store_code' => $storeCode,
                    'store_name' => $storeName,
                    'section' => $storeSubUnitShortName,
                    'order_type' => $orderType,
                    'item_code' => $itemCode,
                    'item_description' => $itemDescription,
                    'category' => $itemCategoryName,
                    'requested' => $orderQuantity,
                    'allocated' => $allocatedQuantity,
                    'received' => $receivedQuantity,
                    'variance' => $variance,
                    'received_by' => $completedById,
                    'date_received' => $completedAt,
                    'receive_type' => $receiveType,
                    'remarks' => $remarks,
                ];
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $reportData);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
