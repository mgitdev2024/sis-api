<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
class StockConversionController extends Controller
{
    use ResponseTrait;

    public function onConvert(Request $request, $stockInventoryId)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'converted_quantity' => 'required|integer',
            'conversion_items' => 'required|json', // [{"ic":"CR PC","q":1},{"ic":"CR 6","q":2}]
        ]);
        try {
            $createdById = $fields['created_by_id'];
            $stockInventoryModel = StockInventoryModel::findOrFail($stockInventoryId);
            $storeCode = $stockInventoryModel->store_code;
            $storeSubUnitShortName = $stockInventoryModel->store_sub_unit_short_name ?? null;
            $itemCode = $stockInventoryModel->item_code;

            return $this->dataResponse('error', 400, __('msg.update_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.update_failed'), $exception->getMessage());
        }
    }
}
