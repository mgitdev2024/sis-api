<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;

class StoreReceivingReportController extends Controller
{
    use ResponseTrait;
    public function onGenerateDeliveryReceivingReport(Request $request)
    {
        try {
            $storeCode = $request->store_code ?? null; // Expected format: ['C001','C002']
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;
            $receivedAtRange = $request->received_at_range ?? null; // Expected format: 'YYYY-MM-DD to YYYY-MM-DD'
            $dateExplode = $receivedAtRange != null ? explode('to', str_replace(' ', '', $receivedAtRange)) : null;
            $dateFrom = isset($dateExplode[0]) ? date('Y-m-d', strtotime($dateExplode[0])) : null;
            $dateTo = isset($dateExplode[1]) ? date('Y-m-d', strtotime($dateExplode[1])) : null;
            $drNumber = $request->dr_number ?? null;
            $orderType = $request->order_type ?? null; // Expected values: 0 (Regular), 1 (Special), 2 (Fan-out) [0,1,2]
            $receivingType = $request->receiving_type ?? null; // Expected values: 0 (Scan), 1 (Manual) [0,1]
            $isShowOnlyNonZeroVariance = $request->is_show_only_non_zero_variance ?? null;
            $storeReceivingInventoryItems = StoreReceivingInventoryItemModel::select([
                'id',
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
                'received_by_id',
                'received_at',
                'remarks',
                'status'
            ]);
            if ($storeCode) {
                $storeCode = json_decode($storeCode);
                $storeReceivingInventoryItems->whereIn('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $storeReceivingInventoryItems->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($dateFrom && $dateTo) {
                $storeReceivingInventoryItems
                    ->where('received_at', '>=', $dateFrom)
                    ->where('received_at', '<', Carbon::parse($dateTo)->addDay()->startOfDay());
            } else if ($dateFrom) {
                $storeReceivingInventoryItems->whereDate('received_at', $dateFrom);
            }
            if ($drNumber) {
                $storeReceivingInventoryItems->where('order_session_id', $drNumber);
            }
            if ($orderType) {
                $orderType = json_decode($orderType);
                $storeReceivingInventoryItems->whereIn('order_type', $orderType);
            }
            if ($receivingType) {
                $receivingType = json_decode($receivingType);
                $storeReceivingInventoryItems->whereIn('receive_type', $receivingType);
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
                $receivedBy = $item['formatted_received_by_label'] ?? null;
                $remarks = $item['remarks'] ?? null;
                $receivedAt = $item['formatted_received_at_label'] ?? null;
                $deliveryDate = $item['formatted_delivery_date_label'] ?? null;
                $status = null;
                $variance = floatval($receivedQuantity) - floatval($allocatedQuantity);

                if ($receivedQuantity < $allocatedQuantity) {
                    $status = 'Short';
                } else if ($receivedQuantity > $allocatedQuantity) {
                    $status = 'Over';
                } else if ($orderQuantity <= 0) {
                    $status = 'Unallocated';
                } else {
                    $status = 'Completed';
                }

                if ($isShowOnlyNonZeroVariance && $variance == 0) {
                    continue; // Skip items with zero variance if the flag is set
                }
                $reportData[] = [
                    'id' => $item['id'],
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
                    'received_by' => $receivedBy,
                    'remarks' => $remarks,
                    'date_received' => $receivedAt,
                    'receive_type' => $receiveType,
                    'status' => $status,
                ];
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $reportData);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
