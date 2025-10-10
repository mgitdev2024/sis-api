<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryCountTemplateModel;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;

class StockInventoryCountTemplateController extends Controller
{
    use ResponseTrait;
    public function onGet($store_code, $sub_unit_short_name)
    {
        try {
            $template = StockInventoryCountTemplateModel::where([
                'store_code' => $store_code,
                'store_sub_unit_short_name' => $sub_unit_short_name
            ])->latest()->first();

            if (!$template) {
                return $this->dataResponse('error', 200, 'No template found for the specified store and sub-unit.');
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $template);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'An error occurred: ' . $exception->getMessage());
        }
    }
}
