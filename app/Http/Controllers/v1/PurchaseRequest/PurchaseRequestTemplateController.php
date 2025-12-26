<?php

namespace App\Http\Controllers\v1\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Sap\PurchaseRequest\PurchaseRequestTemplateModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
class PurchaseRequestTemplateController extends Controller
{
    use ResponseTrait;
    public function onGet($store_code, $sub_unit_short_name)
    {
        try {
            $template = PurchaseRequestTemplateModel::where([
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
