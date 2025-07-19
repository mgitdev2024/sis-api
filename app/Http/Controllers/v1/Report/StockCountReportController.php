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
            $storeCode = $request->store_code ?? null;
            $storeSubUnitShortName = $request->store_sub_unit_short_name ?? null;
            $deliveryDateRange = $request->delivery_date_range ?? null; // Expected format: 'YYYY-MM-DD to YYYY-MM-DD'
            $deliveryDateExplode = $deliveryDateRange != null ? explode('to', str_replace(' ', '', $deliveryDateRange)) : null;
            $deliveryDateFrom = isset($deliveryDateExplode[0]) ? date('Y-m-d', strtotime($deliveryDateExplode[0])) : null;
            $deliveryDateTo = isset($deliveryDateExplode[1]) ? date('Y-m-d', strtotime($deliveryDateExplode[1])) : null;
            $status = $request->status ?? null;

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
                $stockCountModel->where('store_code', $storeCode);
            }
            if ($storeSubUnitShortName) {
                $stockCountModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            if ($deliveryDateFrom && $deliveryDateTo) {
                $stockCountModel->whereBetween('delivery_date', [$deliveryDateFrom, $deliveryDateTo]);
            } else if ($deliveryDateFrom) {
                $stockCountModel->whereDate('delivery_date', $deliveryDateFrom);
            }
            if ($status) {
                $stockCountModel->where('status', $status);
            }
            $stockCountModel = $stockCountModel->whereNotIn('status', [3])->orderBy('reference_number', 'ASC')->get();

            $reportData = [];
            foreach ($stockCountModel as $item) {
                $item->stockInventoryItemsCount->each(function ($countItem) use (&$reportData, $item) {
                    $systemQuantity = $countItem['system_quantity'];
                    $actualQuantity = $countItem['counted_quantity'];
                    $variance = $systemQuantity - $actualQuantity;
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
