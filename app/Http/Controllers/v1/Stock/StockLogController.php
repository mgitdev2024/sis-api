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
    // Create get by id to call prod order and exp date per transaction items
    public function onGet($store_code, $item_code, $sub_unit = null)
    {
        try {
            $stockLogModel = StockLogModel::where([
                'store_code' => $store_code,
                'item_code' => $item_code,
            ]);

            if ($sub_unit != null) {
                $stockLogModel->where('store_sub_unit_short_name', $sub_unit);

            }
            $stockLogModel = $stockLogModel->orderBy('id', 'DESC')
                ->get();
            // ->map(function ($log) {
            //     $log->formatted_created_at = Carbon::parse($log->created_at)
            //         ->timezone('Asia/Manila')
            //         ->format('Y-m-d h:i:a');
            //     return $log;
            // });

            if (count($stockLogModel) <= 0) {
                return $this->dataResponse('error', 200, __('msg.record_not_found'));
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $stockLogModel);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetStockDetails($item_code)
    {
        try {
            $response = \Http::get(env('MGIOS_URL') . '/item-details/get/' . $item_code);
            if ($response->failed()) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'), null);

            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $response->json());

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }

    }
}
