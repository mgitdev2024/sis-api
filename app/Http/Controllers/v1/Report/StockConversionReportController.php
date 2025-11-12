<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockConversionModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
use Carbon\Carbon;

class StockConversionReportController extends Controller
{
    use ResponseTrait;
    public function onGenerateDailyReport(Request $request)
    {
        try {
            $storeCode = $request->store_code ?? null; // Expected format: ['C001','C002']
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;
            $conversionDateRange = $request->conversion_date_range ?? null; // Expected format: 'YYYY-MM-DD to YYYY-MM-DD'
            $conversionType = $request->conversion_type ?? null; // Expected format: [0,1] 0 = Automatic, 1 = Manual
            $dateExplode = $conversionDateRange != null ? explode('to', str_replace(' ', '', $conversionDateRange)) : null;
            $dateFrom = isset($dateExplode[0]) ? date('Y-m-d', strtotime($dateExplode[0])) : null;
            $dateTo = isset($dateExplode[1]) ? date('Y-m-d', strtotime($dateExplode[1])) : null;

            $stockConversionModel = StockConversionModel::from('stock_conversions as sc')
            ->leftJoin('stock_inventories as si', 'sc.item_code', '=', 'si.item_code')
            ->select([
                'sc.*',
                'si.uom',
                'si.is_base_unit'
            ]);
            if ($storeCode) {
                $storeCode = json_decode($storeCode);
                $stockConversionModel->whereIn('sc.store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $stockConversionModel->where('sc.store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($dateFrom && $dateTo) {
                $stockConversionModel->where('sc.created_at', '>=', $dateFrom)
                    ->where('sc.created_at', '<', Carbon::parse($dateTo)->addDay()->startOfDay());
            } elseif ($dateFrom) {
                $stockConversionModel->whereDate('sc.created_at', $dateFrom);
            }
            if ($conversionType) {
                $conversionType = json_decode($conversionType);
                $stockConversionModel->whereIn('sc.type', $conversionType);
            }
            $stockConversionModel = $stockConversionModel->orderBy('sc.reference_number', 'ASC')->get();

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
                        'from_uom' => $item['uom'] ?? null,
                        'from_item_description' => $item['item_description'],
                        'from_qty' => $item['quantity'],
                        'to_item_code' => $conversionItem['item_code'],
                        'to_item_description' => $conversionItem['item_description'],
                        'to_uom' => $conversionItem->stockInventory->uom ?? null,
                        'to_qty' => $conversionItem['converted_quantity'],
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
