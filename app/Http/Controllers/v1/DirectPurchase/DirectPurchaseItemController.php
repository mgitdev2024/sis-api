<?php

namespace App\Http\Controllers\v1\DirectPurchase;

use App\Http\Controllers\Controller;
use App\Models\DirectPurchase\DirectPurchaseItemModel;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use DB, Exception;

class DirectPurchaseItemController extends Controller
{
    use ResponseTrait;
    public function onCreate(Request $request)
    {
        try {
            $directPurchaseItemsData = $request->input('direct_purchase_items', []);
            $createdItems = [];

            foreach ($directPurchaseItemsData as $itemData) {
                $createdItem = DirectPurchaseItemModel::create($itemData);
                $createdItems[] = $createdItem;
            }

            return $this->dataResponse('success', 200, 'Direct Purchase Items Created Successfully.', $createdItems);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'An error occurred: ' . $exception->getMessage());
        }
    }


    public function onUpdateStocks($directPurchaseData, $createdById)
    {
        try {
            DB::beginTransaction();
            $directPurchaseId = $directPurchaseData['direct_purchase_header']->id;
            $directPurchaseItemModel = DirectPurchaseItemModel::where('direct_purchase_id', $directPurchaseId)->get();
            $storeCode = $directPurchaseData['direct_purchase_header']->store_code;
            $storeSubUnitShortName = $directPurchaseData['direct_purchase_header']->store_sub_unit_short_name;
            $referenceNumber = $directPurchaseData['direct_purchase_header']->reference_number;
            foreach ($directPurchaseItemModel as $transactionItems) {
                // Receive Type is temporary
                $itemCode = $transactionItems['item_code'];
                $itemQuantityCount = $transactionItems['quantity'];
                $itemDescription = $transactionItems['item_description'];
                $itemCategoryName = StockInventoryModel::where('item_code', $itemCode)->first()->item_category_name ?? '';

                $stockLogModel = StockLogModel::where([
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'item_code' => $itemCode,
                ])->orderBy('id', 'DESC')->first();

                $currentFinalStock = $stockLogModel->final_stock ?? 0;
                $newStockLogModel = new StockLogModel();
                $newStockLogModel->reference_number = $referenceNumber;
                $newStockLogModel->store_code = $storeCode;
                $newStockLogModel->store_sub_unit_short_name = $storeSubUnitShortName;
                $newStockLogModel->item_code = $itemCode;
                $newStockLogModel->item_description = $itemDescription;
                $newStockLogModel->item_category_name = $itemCategoryName;
                $newStockLogModel->quantity = $itemQuantityCount;
                $newStockLogModel->initial_stock = $currentFinalStock;
                $newStockLogModel->final_stock = $currentFinalStock + $itemQuantityCount;
                $newStockLogModel->transaction_type = 'in';
                $newStockLogModel->transaction_sub_type = 'item request';
                $newStockLogModel->created_by_id = $createdById;
                $newStockLogModel->save();

                $stockInventoryModel = StockInventoryModel::where([
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'item_code' => $itemCode,
                ])->first();
                if ($stockInventoryModel) {
                    $stockInventoryModel->stock_count += $itemQuantityCount;
                    $stockInventoryModel->save();
                }
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, __('msg.update_failed'), $exception->getMessage());
        }

    }
}