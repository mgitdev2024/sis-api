<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryCountModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
class StockCountReportController extends Controller
{
    use ResponseTrait;
    public function onGenerateDailyReport(Request $request)
    {
        try {
            // Store Code & Sub Unit Filters
            $storeCode = $request->store_code ?? null;
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;

            // Date Ranges & Type Filters
            $dateRangeTypeId = $request->date_range_type ?? null; // Expected format: 0, 1, 2 [0 = created_at, 1 = posted_at, 2 = reviewed_at]
            $dateRangeArray = [
                0 => 'created_at',
                1 => 'posted_at',
                2 => 'reviewed_at',
            ];
            $dateRangeType = $dateRangeArray[$dateRangeTypeId] ?? null;
            $dateRange = $request->delivery_date_range ?? null; // Expected format: 'YYYY-MM-DD to YYYY-MM-DD'
            $dateRangeExplode = $dateRange != null ? explode('to', str_replace(' ', '', $dateRange)) : null;
            $dateFrom = isset($dateRangeExplode[0]) ? date('Y-m-d', strtotime($dateRangeExplode[0])) : null;
            $dateTo = isset($dateRangeExplode[1]) ? date('Y-m-d', strtotime($dateRangeExplode[1])) : null;

            // Other Filters
            $status = $request->status ?? null; // Assuming status is passed as a query parameter
            $referenceNumber = $request->reference_number ?? null;
            $isShowOnlyNonZeroVariance = $request->is_show_only_non_zero_variance ?? null;
            $countType = $request->count_type ?? null; // Assuming count_type is passed as a query parameter [1,2,3]

            $stockCountModel = StockInventoryCountModel::select([
                'id',
                'reference_number',
                'created_by_id',
                'created_at',
                'updated_by_id',
                'updated_at',
                'type',
                'store_code',
                'store_sub_unit_short_name',
                'status',
            ]);
            if ($storeCode) {
                $storeCode = json_decode($storeCode);
                $stockCountModel->whereIn('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $stockCountModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if (($dateFrom && $dateTo) && $dateRangeType) {
                $stockCountModel->whereBetween($dateRangeType, [$dateFrom, $dateTo]);
            } else if ($dateFrom && $dateRangeType) {
                $stockCountModel->whereDate($dateRangeType, $dateFrom);
            }
            if ($status) {
                $stockCountModel->where('status', $status);
            }
            if ($referenceNumber) {
                $stockCountModel->where('reference_number', $referenceNumber);
            }
            if ($countType) {
                $countType = json_decode($countType);
                $stockCountModel->whereIn('type', $countType);
            }

            $stockCountModel = $stockCountModel->whereNotIn('status', [3])->orderBy('reference_number', 'ASC')->get();

            $reportData = [];
            foreach ($stockCountModel as $item) {
                $item->stockInventoryItemsCount->each(function ($countItem) use (&$reportData, $item, $isShowOnlyNonZeroVariance) {
                    $systemQuantity = $countItem['system_quantity'];
                    $actualQuantity = $countItem['counted_quantity'];
                    $variance = $systemQuantity - $actualQuantity;
                    if ($isShowOnlyNonZeroVariance && $variance == 0) {
                        return; // Skip if variance is zero and filter is applied
                    }
                    $status = $item['status'];
                    $postedBy = null;
                    $postedAt = null;
                    if ($status == 2) {
                        $postedBy = $item['formatted_updated_by_label'];
                        $postedAt = $item['formatted_updated_at_label'];
                    }
                    $reportData[] = [
                        'id' => $countItem->id,
                        'reference_number' => $item['reference_number'],
                        'created_by' => $item['formatted_created_by_label'],
                        'created_at' => $item['formatted_created_at_label'],
                        'type' => $item['type_label'],
                        'store_code' => $item['store_code'],
                        'store_name' => $item['formatted_store_name_label'],
                        'store_sub_unit_short_name' => $item['store_sub_unit_short_name'] ?? null,
                        'item_code' => $countItem['item_code'],
                        'item_description' => $countItem['item_description'],
                        'status' => $item['status_label'],
                        'system_qty' => $systemQuantity,
                        'actual_qty' => $actualQuantity,
                        'variance' => $variance,
                        'posted_by' => $postedBy,
                        'date_posted' => $postedAt
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
