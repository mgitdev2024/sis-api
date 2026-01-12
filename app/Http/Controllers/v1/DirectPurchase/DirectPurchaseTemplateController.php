<?php

namespace App\Http\Controllers\v1\DirectPurchase;

use App\Http\Controllers\Controller;
use App\Models\DirectPurchase\DirectPurchaseModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;


class DirectPurchaseTemplateController extends Controller
{
    use ResponseTrait;
    public function onGet($store_code, $sub_unit_short_name)
    {
        try {
            $template = DirectPurchaseModel::where([
                'store_code' => $store_code,
                'store_sub_unit_short_name' => $sub_unit_short_name
            ])->latest()->first();

            if (!$template) {
                return $this->dataResponse('success', 200, 'No template found for the specified store and sub-unit.');
            }

            // Fetch only requested_quantity and item_code for the latest PR's items
            // $items = $template->directPurchaseItems()
            //     ->get(['requested_quantity', 'item_code']);
            $items = $template->directPurchaseItems()
                ->get(['requested_quantity', 'item_code'])
                ->map(function ($item) {
                    return [
                        'requested_quantity' => $item->requested_quantity,
                        'item_code' => $item->item_code,
                    ];
                })->values();


            return $this->dataResponse('success', 200, __('msg.record_found'), $items);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'An error occurred: ' . $exception->getMessage());
        }
    }
}