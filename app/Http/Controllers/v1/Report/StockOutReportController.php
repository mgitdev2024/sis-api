<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockOutModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;

class StockOutReportController extends Controller
{
    use ResponseTrait;

    public function onGenerateDailyReport(Request $request)
    {
        try {
            $storeCode = $request->store_code ?? null; // Expected format: ['C001','C002']
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;
            $stockOutDateRange = $request->stock_out_date_range ?? null; // Expected format: 'YYYY-MM-DD to YYYY-MM-DD'
            $dateRangeExplode = $stockOutDateRange != null ? explode('to', str_replace(' ', '', $stockOutDateRange)) : null;
            $dateFrom = isset($dateRangeExplode[0]) ? date('Y-m-d', strtotime($dateRangeExplode[0])) : null;
            $dateTo = isset($dateRangeExplode[1]) ? date('Y-m-d', strtotime($dateRangeExplode[1])) : null;
            $referenceNumber = $request->reference_number ?? null;

            $stockOutModel = StockOutModel::query();
            if ($storeCode) {
                $storeCode = json_decode($storeCode);
                $stockOutModel->whereIn('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $stockOutModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($dateFrom && $dateTo) {
                $stockOutModel->where('created_at', '>=', $dateFrom)
                    ->where('created_at', '<', Carbon::parse($dateTo)->addDay()->startOfDay());
                ;
            } elseif ($dateFrom) {
                $stockOutModel->whereDate('created_at', $dateFrom);
            }
            if ($referenceNumber) {
                $stockOutModel->where('reference_number', $referenceNumber);
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
                        'uom' => $outItem->stockInventory->uom ?? null,
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
