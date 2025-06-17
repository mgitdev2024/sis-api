<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockOutItemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class StockOutItemController extends Controller
{
    public function onCreateStockOutItem($stockOutItems, $stockOutId, $createdById)
    {
        // [{"ic":"CR 12","idc":"Cheeseroll Box of 12","icn":"BREADS","icv":"Mini","uom":"Box","q":1}]
        try {
            DB::beginTransaction();
            foreach ($stockOutItems as $item) {
                $itemCode = $item['ic'];
                $itemDescription = $item['idc'];
                $itemCategoryName = $item['icn'];
                $itemVariantName = $item['icv'];
                $unitOfMeasure = $item['uom'];
                $quantity = $item['q'] ?? 0;

                StockOutItemModel::insert([
                    'stock_out_id' => $stockOutId,
                    'created_by_id' => $createdById,
                    'item_code' => $itemCode,
                    'item_description' => $itemDescription,
                    'item_category_name' => $itemCategoryName,
                    'item_variant_name' => $itemVariantName,
                    'unit_of_measure' => $unitOfMeasure,
                    'quantity' => $quantity,
                    'created_at' => now(),
                ]);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception(__('msg.create_failed'), 400, $exception);
        }
    }
}
