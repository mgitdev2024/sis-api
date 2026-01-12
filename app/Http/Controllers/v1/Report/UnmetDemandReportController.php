<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockOutModel;
use App\Models\UnmetDemand\UnmetDemandModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;

class UnmetDemandReportController extends Controller
{
    use ResponseTrait;

    public function onGenerateDailyReport(Request $request)
    {
        try {
            $storeCode = $request->store_code ?? null; // Expected format: ['C001','C002']
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;
            $unmetDateRange = $request->stock_out_date_range ?? null; // Expected format: 'YYYY-MM-DD to YYYY-MM-DD'
            $dateRangeExplode = $unmetDateRange != null ? explode('to', str_replace(' ', '', $unmetDateRange)) : null;
            $dateFrom = isset($dateRangeExplode[0]) ? date('Y-m-d', strtotime($dateRangeExplode[0])) : null;
            $dateTo = isset($dateRangeExplode[1]) ? date('Y-m-d', strtotime($dateRangeExplode[1])) : null;
            $referenceNumber = $request->reference_number ?? null;

            $unmetDemandModel = UnmetDemandModel::query();
            if ($storeCode) {
                $storeCode = json_decode($storeCode);
                $unmetDemandModel->whereIn('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $unmetDemandModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($dateFrom && $dateTo) {
                $unmetDemandModel->where('created_at', '>=', $dateFrom)
                    ->where('created_at', '<', Carbon::parse($dateTo)->addDay()->startOfDay());
                ;
            } elseif ($dateFrom) {
                $unmetDemandModel->whereDate('created_at', $dateFrom);
            }
            if ($referenceNumber) {
                $unmetDemandModel->where('reference_number', $referenceNumber);
            }
            $unmetDemandModel = $unmetDemandModel->orderBy('reference_number', 'ASC')->get();

            $reportData = [];
            foreach ($unmetDemandModel as $item) {

                $item->unmetDemandItems->each(function ($unmetItems) use (&$reportData, $item) {
                    $reportData[] = [
                        'id' => $unmetItems->id,
                        'reference_number' => $item['reference_number'],
                        'store_code' => $item['store_code'],
                        'store_name' => $item['formatted_store_name_label'],
                        'store_sub_unit_short_name' => $item['store_sub_unit_short_name'] ?? null,
                        'item_code' => $unmetItems['item_code'],
                        'item_description' => $unmetItems['item_description'],
                        'uom' => $unmetItems->stockInventory->uom ?? null,
                        'quantity' => $unmetItems['quantity'],
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