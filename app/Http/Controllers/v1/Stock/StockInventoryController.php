<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryModel;
use Illuminate\Http\Request;
use Exception;
use App\Traits\ResponseTrait;
use DB;
class StockInventoryController extends Controller
{
    use ResponseTrait;

    public function onGet($store_code, $sub_unit = null)
    {
        try {
            $stockInventoryModel = StockInventoryModel::where('store_code', $store_code);
            if ($sub_unit != null) {
                $stockInventoryModel->where('store_sub_unit_short_name', $sub_unit);
            }
            $stockInventoryModel = $stockInventoryModel->orderBy('status', 'DESC')->orderBy('item_code', 'ASC')->get();

            if (count($stockInventoryModel) <= 0) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'), null);
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $stockInventoryModel);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
