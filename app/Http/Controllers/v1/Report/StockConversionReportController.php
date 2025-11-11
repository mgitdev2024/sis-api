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

            $stockConversionModel = StockConversionModel::query();
            if ($storeCode) {
                $storeCode = json_decode($storeCode);
                $stockConversionModel->whereIn('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $stockConversionModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($dateFrom && $dateTo) {
                $stockConversionModel->where('created_at', '>=', $dateFrom)
                    ->where('created_at', '<', Carbon::parse($dateTo)->addDay()->startOfDay());
            } else if ($dateFrom) {
                $stockConversionModel->whereDate('created_at', $dateFrom);
            }
            if ($conversionType) {
                $conversionType = json_decode($conversionType);
                $stockConversionModel->whereIn('type', $conversionType);
            }
            $stockConversionModel = $stockConversionModel->orderBy('reference_number', 'ASC')->get();

            $itemCodes = $stockConversionModel->pluck('item_code')->unique()->toArray();
            $uomData = $this->getUomData($itemCodes); // ['CR 12' => 'BOX', 'FG0001' => 'PIECE']

            $reportData = [];
            foreach ($stockConversionModel as $item) {

                $itemCodes= $stockConversionModel->pluck('item_code')->unique()->toArray();
                $convertedUomData = $this->getUomData($itemCodes); // ['CR 12' => 'BOX', 'FG0001' => 'PIECE']
                $item->stockConversionItems->each(function ($conversionItem) use (&$reportData, $item, $uomData, $convertedUomData) {
                    $reportData[] = [
                        'id' => $conversionItem->id,
                        'reference_number' => $item['reference_number'],
                        'store_code' => $item['store_code'],
                        'store_name' => $item['formatted_store_name_label'],
                        'store_sub_unit_short_name' => $item['store_sub_unit_short_name'] ?? null,
                        'from_item_code' => $item['item_code'],
                        'from_uom' => $uomData[$item['item_code']] ?? null,
                        'from_item_description' => $item['item_description'],
                        'from_qty' => $item['quantity'],
                        'to_item_code' => $conversionItem['item_code'],
                        'to_item_description' => $conversionItem['item_description'],
                        'to_uom' => $convertedUomData[$conversionItem['item_code']] ?? null,
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

    private function getUomData($itemCodes)
    {
        $uomData = [];
        $response = \Http::withHeaders([
            'x-api-key' => config('apikeys.mgios_api_key'),
        ])->post(
                config('apiurls.mgios.url') . config('apiurls.mgios.public_item_uom_get'),
                ['item_code_collection' => json_encode($itemCodes)]
            );

        if ($response->successful()) {
            $uomData = $response->json();
        }

        return $uomData;
    }
}
