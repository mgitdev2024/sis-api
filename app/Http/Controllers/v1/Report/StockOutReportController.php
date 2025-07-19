<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockOutModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
class StockOutReportController extends Controller
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

            $stockOutModel = StockOutModel::query();
            if ($storeCode) {
                $stockOutModel->where('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $stockOutModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($deliveryDateFrom && $deliveryDateTo) {
                $stockOutModel->whereBetween('delivery_date', [$deliveryDateFrom, $deliveryDateTo]);
            } else if ($deliveryDateFrom) {
                $stockOutModel->whereDate('delivery_date', $deliveryDateFrom);
            }
            $stockOutModel = $stockOutModel->orderBy('reference_number', 'ASC')->get();

            $reportData = [];
            foreach ($stockOutModel as $item) {

                $item->stockOutItems->each(function ($outItem) use (&$reportData, $item) {
                    $reportData[] = [
                        'id' => $outItem->id,
                        'reference_number' => $item['reference_number'],
                        'store_code' => $item['store_code'],
                        'store_name' => $item['formatted_store_name_label'],
                        'store_sub_unit_short_name' => $item['store_sub_unit_short_name'] ?? null,
                        'item_code' => $outItem['item_code'],
                        'item_description' => $outItem['item_description'],
                        'issued_qty' => $outItem['quantity'],
                        'date_issued' => $item['formatted_stock_out_date_report_label'] ?? null
                    ];
                });
            }
            if (empty($reportData)) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $reportData);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
