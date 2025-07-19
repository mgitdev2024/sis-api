<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockTransferModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
class StockTransferReportController extends Controller
{
    use ResponseTrait;
    public function onGenerateDailyReport(Request $request)
    {
        try {
            $storeCode = $request->store_code ?? null;
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;
            $deliveryDateRange = $request->delivery_date_range ?? null; // Expected format: 'YYYY-MM-DD to YYYY-MM-DD'
            $deliveryDateExplode = $deliveryDateRange != null ? explode('to', str_replace(' ', '', $deliveryDateRange)) : null;
            $deliveryDateFrom = isset($deliveryDateExplode[0]) ? date('Y-m-d', strtotime($deliveryDateExplode[0])) : null;
            $deliveryDateTo = isset($deliveryDateExplode[1]) ? date('Y-m-d', strtotime($deliveryDateExplode[1])) : null;
            $status = $request->status ?? null; // Assuming status is passed as a query parameter
            $stockTransferModel = StockTransferModel::select([
                'id',
                'reference_number',
                'store_code',
                'store_sub_unit_short_name',
                'transfer_type',
                'transportation_type',
                'location_code',
                'location_name',
                'location_sub_unit',
                'store_received_by_id',
                'store_received_at',
                'created_by_id',
                'created_at',
                'pickup_date',
                'logistics_picked_up_at',
                'status'
            ])->whereIn('transfer_type', [0, 2]);
            if ($storeCode) {
                $stockTransferModel->where('store_code', $storeCode);
            }
            if ($status) {
                $stockTransferModel->where('status', $status);
            }
            if ($storeSubUnitShortName) {
                $stockTransferModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($deliveryDateFrom && $deliveryDateTo) {
                $stockTransferModel->whereBetween('delivery_date', [$deliveryDateFrom, $deliveryDateTo]);
            } else if ($deliveryDateFrom) {
                $stockTransferModel->whereDate('delivery_date', $deliveryDateFrom);
            }
            $stockTransferModel = $stockTransferModel->orderBy('id', 'ASC')->get();

            $reportData = [];
            foreach ($stockTransferModel as $item) {
                $item->stockTransferItems->each(function ($transferItem) use (&$reportData, $item) {
                    $reportData[] = [
                        'reference_number' => $item['reference_number'],
                        'transferred_by' => $item['created_by_name_label'] ?? null,
                        'date_created' => $item['formatted_created_at_label'] ?? null,
                        'scheduled_pickup_date' => $item['pickup_date'],
                        'actual_pickup_date' => $item['formatted_logistics_picked_up_at_report_label'] ?? null,
                        'transport_type' => $item['transportation_type_label'] ?? null,
                        'from_store_code' => $item['store_code'],
                        'from_store_name' => $item['formatted_store_name_label'] ?? null,
                        'from_store_sub_unit' => $item['store_sub_unit_short_name'],
                        'to_store_code' => $item['location_code'],
                        'to_store_name' => $item['location_name'],
                        'to_store_sub_unit' => $item['location_sub_unit'],
                        'item_code' => $transferItem['item_code'],
                        'item_description' => $transferItem['item_description'],
                        'status' => $item['status_label'] ?? null,
                        'allocated' => $transferItem['quantity'] ?? 0,
                        'warehouse_receive' => 0,
                        'variance' => 0,
                        'received' => 0,
                        'received_by' => $item['formatted_store_received_by_label'] ?? null,
                        'received_at' => $item['formatted_store_received_at_label'] ?? null,
                    ];
                });
            }
            if (empty($reportData)) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }

            foreach ($reportData as &$data) {
                $referenceNumber = $data['reference_number'];
                $storeCode = $data['from_store_code'];
                $storeSubUnitShortName = $data['from_store_sub_unit'] ?? null;
                $itemCode = $data['item_code'];
                $storeReceivingInventoryItemModel = StoreReceivingInventoryItemModel::where([
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'item_code' => $itemCode
                ])->orderBy('id', 'DESC')->first();

                if ($storeReceivingInventoryItemModel) {
                    $receivedQuantity = $storeReceivingInventoryItemModel->received_quantity ?? 0;
                    $referenceNumberBase = explode('-', $data['reference_number'])[0];

                    if ($referenceNumberBase == "SWS") {
                        $response = \Http::withHeaders([
                            'x-api-key' => env('MGIOS_API_KEY'),
                        ])->get(env('MGIOS_URL') . "/public/receiving/stock/transfer/get/$referenceNumber/$itemCode");

                        if ($response->successful()) {
                            $warehouseReceived = $response->json()['stock_transfer_items'][0]['quantity'] ?? 0;
                            $data['warehouse_receive'] = $warehouseReceived;
                        }
                    }
                    $data['received'] = $receivedQuantity;
                    $data['variance'] = $data['received'] - $data['warehouse_receive'];
                }
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $reportData);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
