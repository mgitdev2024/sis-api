<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockConversionModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;

class StockConversionReportController extends Controller
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

            $stockConversionModel = StockConversionModel::query();
            if ($storeCode) {
                $stockConversionModel->where('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $stockConversionModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($deliveryDateFrom && $deliveryDateTo) {
                $stockConversionModel->whereBetween('delivery_date', [$deliveryDateFrom, $deliveryDateTo]);
            } else if ($deliveryDateFrom) {
                $stockConversionModel->whereDate('delivery_date', $deliveryDateFrom);
            }
            $stockConversionModel = $stockConversionModel->orderBy('reference_number', 'ASC')->get();

            $reportData = [];
            foreach ($stockConversionModel as $item) {

                $item->stockConversionItems->each(function ($conversionItem) use (&$reportData, $item) {
                    $reportData[] = [
                        'id' => $conversionItem->id,
                        'reference_number' => $item['reference_number'],
                        'store_code' => $item['store_code'],
                        'store_name' => $item['formatted_store_name_label'],
                        'store_sub_unit_short_name' => $item['store_sub_unit_short_name'] ?? null,
                        'from_item_code' => $item['item_code'],
                        'from_item_description' => $item['item_description'],
                        'from_qty' => $item['quantity'],
                        'to_item_code' => $conversionItem['item_code'],
                        'to_item_description' => $conversionItem['item_description'],
                        'to_qty' => $conversionItem['quantity'],
                        'conversion_type' => $item['type'] == 0 ? 'Automatic' : 'Manual',
                        'converted_by' => $item['created_by_name_label'] ?? null,
                        'conversion_data' => $item['formatted_created_at_label'] ?? null
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
