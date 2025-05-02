<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockTransferItemModel;
use App\Traits\Stock\StockTrait;
use Illuminate\Http\Request;
use DB;
use Exception;
use App\Traits\ResponseTrait;
class StockTransferItemController extends Controller
{
    use ResponseTrait, StockTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'stock_transfer_id' => 'required|integer',
            'store_code' => 'required|string',
            'store_sub_unit_short_name' => 'required|string',
            'reference_number' => 'required|string',
            'transfer_items' => 'required|json', // [{"ic":"CR 12","q":12,"ict":"Breads","icd":"Cheeseroll Box of 12"}]
            'created_by_id' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $stockTransferId = $fields['stock_transfer_id'];
            $transferItems = json_decode($fields['transfer_items'], true);
            $createdById = $fields['created_by_id'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];
            $referenceNumber = $fields['reference_number'];
            foreach ($transferItems as $item) {
                $itemCode = $item['ic'];
                $itemDescription = $item['icd'];
                $itemCategoryName = $item['ict'];
                $quantity = $item['q'];

                StockTransferItemModel::insert([
                    'stock_transfer_id' => $stockTransferId,
                    'item_code' => $itemCode,
                    'item_description' => $itemDescription,
                    'item_category_name' => $itemCategoryName,
                    'quantity' => $quantity,
                    'created_by_id' => $createdById,
                    'created_at' => now(),
                ]);

                // Update the Stock Log and Inventory
                $this->onCreateStockLogs('stock_out', $storeCode, $storeSubUnitShortName, $createdById, 'manual', null, $item, $referenceNumber, $itemDescription, $itemCategoryName);
            }

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, __('msg.create_failed'), $exception->getMessage());

        }
    }
}
