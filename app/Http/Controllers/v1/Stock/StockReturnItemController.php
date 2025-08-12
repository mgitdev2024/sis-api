<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Models\Stock\StockReturnItemModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;
use Exception;
use DB;
class StockReturnItemController extends Controller
{
    use CrudOperationsTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'item_code' => 'required',
            'item_description' => 'required',
            'item_category_name' => 'required',
            'store_code' => 'required',
            'store_sub_unit_short_name' => 'nullable',
            'quantity' => 'required',
            'created_by_id' => 'required'
        ]);
        try {
            $referenceNumber = StockReturnItemModel::onGenerateReferenceNumber();
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];
            $itemCode = $fields['item_code'];
            $quantity = $fields['quantity'];
            $createdById = $fields['created_by_id'];
            $itemDescription = $fields['item_description'];
            $itemCategoryName = $fields['item_category_name'];

            DB::beginTransaction();
            $stockReturnItem = StockReturnItemModel::create([
                'reference_number' => $referenceNumber,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'item_code' => $itemCode,
                'quantity' => $quantity,
                'created_by_id' => $createdById,
            ]);

            $existingStockLog = StockLogModel::where([
                'store_code' => $storeCode,
                'item_code' => $itemCode,
            ]);
            if ($storeSubUnitShortName != null) {
                $existingStockLog->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $existingStockLog = $existingStockLog->first();

            $initialStock = 0;
            if ($existingStockLog) {
                $initialStock = $existingStockLog->final_stock;
            }
            $finalStock = $initialStock + $quantity;

            StockLogModel::create([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'item_code' => $itemCode,
                'item_description' => $itemDescription,
                'item_category_name' => $itemCategoryName,
                'reference_number' => $referenceNumber,
                'initial_stock' => $initialStock,
                'final_stock' => $finalStock,
                'quantity' => $quantity,
                'transaction_type' => 'in',
                'transaction_sub_type' => 'converted',
                'created_by_id' => $createdById,
            ]);

            // Deduct the Converted Item Code
            $convertedItemStockInventoryModel = StockInventoryModel::where([
                'store_code' => $storeCode,
                'item_code' => $itemCode,
            ]);
            if ($storeSubUnitShortName != null) {
                $convertedItemStockInventoryModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $convertedItemStockInventoryModel = $convertedItemStockInventoryModel->first();
            $convertedItemStockInventoryModel->update([
                'stock_count' => $convertedItemStockInventoryModel->stock_count + $quantity,
                'updated_by_id' => $createdById,
                'updated_at' => now(),
            ]);
            DB::commit();

            return $this->dataResponse('success', 201, __('msg.create_success'), $stockReturnItem);
        } catch (Exception $e) {
            return $this->dataResponse('error', 400, __('msg.create_failed'), $e->getMessage());
        }
    }
}
