<?php

namespace App\Http\Controllers\v1\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Sap\PurchaseRequest\PurchaseRequestItemModel;
use App\Models\Sap\PurchaseRequest\PurchaseRequestModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
class PurchaseRequestTemplateController extends Controller
{
    use ResponseTrait;
    public function onGet($store_code, $sub_unit_short_name)
    {
        try {
            $template = PurchaseRequestModel::where([
                'store_code' => $store_code,
                'store_sub_unit_short_name' => $sub_unit_short_name
            ])->latest()->first();

            if (!$template) {
                return $this->dataResponse('success', 200, 'No template found for the specified store and sub-unit.');
            }

            // Fetch only requested_quantity and item_code for the latest PR's items
            $items = $template->purchaseRequestItems()
                ->get(['requested_quantity', 'item_code']);

            return $this->dataResponse('success', 200, __('msg.record_found'), $items);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'An error occurred: ' . $exception->getMessage());
        }
    }
}
