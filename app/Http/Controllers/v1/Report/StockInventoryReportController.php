<?php

namespace App\Http\Controllers\v1\Report;

use App\Http\Controllers\Controller;
use App\Jobs\Report\StockInventoryDailyMovementReportJob;
use App\Traits\Report\GeneratedReportDataTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use Str;
use Throwable;

class StockInventoryReportController extends Controller
{
    use ResponseTrait;
    use GeneratedReportDataTrait;

    public function onGenerateDailyMovementReport(Request $request)
    {
        $fields = $request->validate([
            'store_code' => 'required|string|max:50',
            'store_sub_unit_short_name' => 'required|string|max:5',
            'transaction_date' => 'nullable',
            'is_group_by_item_category' => 'nullable|boolean',
            'is_group_by_item_description' => 'nullable|boolean',
            'is_show_only_non_zero_variance' => 'nullable|boolean',
            'department_id' => 'required',
            'created_by_id' => 'required',
        ]);
        try {
            $uuid = (string) Str::uuid();
            $fields['uuid'] = $uuid;
            StockInventoryDailyMovementReportJob::dispatch($fields);
            $this->initializeRecord(
                $uuid,
                'Stock Inventory Daily Movement Report',
                $fields['created_by_id'],
                $fields['transaction_date']
            );
            return $this->dataResponse('success', 200, 'Report generated successfully.');

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
