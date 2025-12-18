<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryCountModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;

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
                $stockCountModel->where($dateRangeType, '>=', $dateFrom)
                    ->where($dateRangeType, '<', Carbon::parse($dateTo)->addDay()->startOfDay());
            } elseif ($dateFrom && $dateRangeType) {
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

            // Eager load relationships to prevent N+1 queries
            $stockCountModel = $stockCountModel
                ->with([
                    'stockInventoryItemsCount' => function ($query) use ($isShowOnlyNonZeroVariance) {
                        // Only load items with non-zero variance if filter is applied
                        if ($isShowOnlyNonZeroVariance) {
                            $query->whereRaw('system_quantity != counted_quantity');
                        }
                    },
                    'stockInventoryItemsCount.stockInventory:item_code,uom'
                ])
                ->whereNotIn('status', [3])
                ->orderBy('reference_number', 'ASC')
                ->get();

            // Pre-load all users to avoid repeated queries
            $userIds = $stockCountModel->pluck('created_by_id')
                ->merge($stockCountModel->where('status', 2)->pluck('updated_by_id'))
                ->filter()
                ->unique();

            $users = \App\Models\User::whereIn('employee_id', $userIds)
                ->get()
                ->keyBy('employee_id')
                ->map(function ($user) {
                    return $user->first_name . ' ' . $user->last_name;
                });

            // Pre-load store names to avoid repeated queries
            $storeCodes = $stockCountModel->pluck('store_code')->unique();
            $storeNames = \App\Models\Store\StoreReceivingInventoryItemModel::select('store_code', 'store_name')
                ->whereIn('store_code', $storeCodes)
                ->groupBy('store_code', 'store_name')
                ->get()
                ->pluck('store_name', 'store_code');

            $reportData = [];
            foreach ($stockCountModel as $item) {
                $createdBy = $users[$item->created_by_id] ?? null;
                $storeName = $storeNames[$item->store_code] ?? null;
                $typeLabel = $item->type_label;
                $statusLabel = $item->status_label;
                $createdAt = $item->created_at ? $item->created_at->format('Y-m-d h:i A') : null;

                $postedBy = null;
                $postedAt = null;
                if ($item->status == 2) {
                    $postedBy = $users[$item->updated_by_id] ?? null;
                    $postedAt = $item->updated_at ? $item->updated_at->format('Y-m-d h:i A') : null;
                }

                foreach ($item->stockInventoryItemsCount as $countItem) {
                    $systemQuantity = $countItem->system_quantity;
                    $actualQuantity = $countItem->counted_quantity;
                    $variance = $systemQuantity - $actualQuantity;

                    $reportData[] = [
                        'id' => $countItem->id,
                        'reference_number' => $item->reference_number,
                        'created_by' => $createdBy,
                        'created_at' => $createdAt,
                        'type' => $typeLabel,
                        'store_code' => $item->store_code,
                        'store_name' => $storeName,
                        'store_sub_unit_short_name' => $item->store_sub_unit_short_name,
                        'item_code' => $countItem->item_code,
                        'uom' => $countItem->stockInventory->uom ?? null,
                        'item_description' => $countItem->item_description,
                        'status' => $statusLabel,
                        'system_qty' => $systemQuantity,
                        'actual_qty' => $actualQuantity,
                        'variance' => $variance,
                        'posted_by' => $postedBy,
                        'date_posted' => $postedAt,
                        'remarks' => $countItem->remarks,
                    ];
                }
            }
            if (empty($reportData)) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }
            \Log::info(json_encode($reportData));
            return $this->dataResponse('success', 200, __('msg.record_found'), $reportData);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
