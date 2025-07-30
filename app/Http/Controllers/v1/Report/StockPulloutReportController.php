<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockTransferModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use DB;
use Exception;
class StockPulloutReportController extends Controller
{
    use ResponseTrait;
    public function onGenerateDailyReport(Request $request)
    {
        try {
            // Store Filters
            $storeCode = $request->store_code ?? null;
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;

            // Date Ranges & Type Filters
            $dateRangeTypeId = $request->date_range_type ?? null; // Expected format: 0, 1, 2 [0 = created_at, 1 = scheduled_pickup_date, 2 = actual_pickup_date]
            $dateRangeArray = [
                0 => 'created_at',
                1 => 'pickup_date',
                2 => 'logistics_picked_up_at',
            ];
            $dateRangeType = $dateRangeArray[$dateRangeTypeId];
            $dateRange = $request->delivery_date_range ?? null; // Expected format: 'YYYY-MM-DD to YYYY-MM-DD'
            $dateRangeExplode = $dateRange != null ? explode('to', str_replace(' ', '', $dateRange)) : null;
            $dateFrom = isset($dateRangeExplode[0]) ? date('Y-m-d', strtotime($dateRangeExplode[0])) : null;
            $dateTo = isset($dateRangeExplode[1]) ? date('Y-m-d', strtotime($dateRangeExplode[1])) : null;

            // Other Filters
            $status = $request->status ?? null; // Assuming status is passed as a query parameter
            $referenceNumber = $request->reference_number ?? null;
            $isShowOnlyNonZeroVariance = $request->is_show_only_non_zero_variance ?? null;

            $stockTransferModel = StockTransferModel::select([
                'id',
                'reference_number',
                'store_code',
                'store_sub_unit_short_name',
                'transfer_type',
                'transportation_type',
                'store_received_by_id',
                'store_received_at',
                'created_by_id',
                'created_at',
                'pickup_date',
                'logistics_picked_up_at',
                'status'
            ])->whereIn('transfer_type', [1]);
            if ($storeCode) {
                $storeCode = json_decode($storeCode);
                $stockTransferModel->whereIn('store_code', $storeCode);
            }
            if ($status) {
                $stockTransferModel->where('status', $status);
            }
            if ($storeSubUnitShortName) {
                $stockTransferModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($dateFrom && $dateTo) {
                $stockTransferModel->whereBetween($dateRangeType, [$dateFrom, $dateTo]);
            } else if ($dateFrom) {
                $stockTransferModel->whereDate($dateRangeType, $dateFrom);
            }
            if ($referenceNumber) {
                $stockTransferModel->where('reference_number', $referenceNumber);
            }
            $stockTransferModel = $stockTransferModel->orderBy('id', 'ASC')->get();

            $reportData = [];
            foreach ($stockTransferModel as $item) {
                $item->stockTransferItems->each(function ($transferItem) use (&$reportData, $item) {
                    $reportData[] = [
                        'id' => $transferItem->id,
                        'reference_number' => $item['reference_number'],
                        'pulled_out_by' => $item['created_by_name_label'] ?? null,
                        'date_created' => $item['formatted_created_at_report_label'] ?? null,
                        'scheduled_pickup_date' => $item['pickup_date'],
                        'actual_pickup_date' => $item['formatted_logistics_picked_up_at_report_label'] ?? null,
                        'reason' => $item['remarks'] ?? null,
                        'transport_type' => $item['transportation_type_label'] ?? null,
                        'store_code' => $item['store_code'],
                        'store_name' => $item['formatted_store_name_label'] ?? null,
                        'store_sub_unit' => $item['store_sub_unit_short_name'],
                        'item_code' => $transferItem['item_code'],
                        'item_description' => $transferItem['item_description'],
                        'status' => $item['status_label'] ?? null,
                        'allocated' => $transferItem['quantity'] ?? 0,
                        'pulled_out_quantity' => $transferItem['quantity'] ?? 0,
                        'warehouse_receive' => 0,
                        'variance' => 0,
                        'received_by' => $item['formatted_store_received_by_label'] ?? null,
                        'received_at' => $item['formatted_store_received_at_label'] ?? null,
                    ];
                });
            }
            if (empty($reportData)) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }

            foreach ($reportData as $key => &$data) {
                $stockTransferId = $data['id'];
                $referenceNumber = $data['reference_number'];
                $storeCode = $data['store_code'];
                $storeSubUnitShortName = $data['store_sub_unit'] ?? null;
                $itemCode = $data['item_code'];
                $storeReceivingInventoryItemModel = StoreReceivingInventoryItemModel::where([
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'item_code' => $itemCode
                ])->orderBy('id', 'DESC')->first();

                if ($storeReceivingInventoryItemModel) {
                    $referenceNumberBase = explode('-', $data['reference_number'])[0];

                    if ($referenceNumberBase == "PT") {
                        $response = \Http::withHeaders([
                            'x-api-key' => env('MGIOS_API_KEY'),
                        ])->get(env('MGIOS_URL') . "/public/stock-adjustment/pullout/get/$stockTransferId/$itemCode");

                        if ($response->successful()) {
                            $warehouseReceived = $response->json()['quantity'] ?? 0;
                            $data['warehouse_receive'] = $warehouseReceived;
                        }
                    }
                    $variance = $data['pulled_out_quantity'] - $data['warehouse_receive'];
                    $data['variance'] = $variance;

                    if ($isShowOnlyNonZeroVariance && $variance == 0) {
                        unset($reportData[$key]);
                    }
                }
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $reportData);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
