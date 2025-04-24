<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockLogModel;
use Illuminate\Http\Request;
use Exception;
use App\Traits\ResponseTrait;
use DB;
use Carbon\Carbon;

class StockLogController extends Controller
{
    use ResponseTrait;
    public function onGet($store_code, $sub_unit, $item_code)
    {
        try {
            $stockLogModel = StockLogModel::where([
                'store_code' => $store_code,
                'store_sub_unit_short_name' => $sub_unit,
                'item_code' => $item_code,
            ])
                ->orderBy('id', 'DESC')
                ->get()
                ->map(function ($log) {
                    $log->formatted_created_at = Carbon::parse($log->created_at)
                        ->timezone('Asia/Manila')
                        ->format('Y-m-d h:i:a');
                    return $log;
                });

            if (count($stockLogModel) <= 0) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $stockLogModel);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
