<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockLogModel;
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
            'store_sub_unit_short_name' => 'nullable|string',
            'reference_number' => 'required|string',
            'transfer_items' => 'required|json', // [{"ic":"CR 12","q":12,"ict":"Breads","icd":"Cheeseroll Box of 12"}]
            'created_by_id' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $stockTransferId = $fields['stock_transfer_id'];
            $transferItems = json_decode($fields['transfer_items'], true);
            $createdById = $fields['created_by_id'];
            // $storeCode = $fields['store_code'];
            // $storeSubUnitShortName = $fields['store_sub_unit_short_name'] ?? null;
            // $referenceNumber = $fields['reference_number'];
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
            }

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, __('msg.create_failed'), $exception->getMessage());

        }
    }

    public function onUpdateStocks($storeTransferModel, $storeTransferId, $createdById)
    {
        try {
            DB::beginTransaction();
            $stockTransferItemModel = StockTransferItemModel::where('stock_transfer_id', $storeTransferId)->get();
            $storeCode = $storeTransferModel->store_code;
            $storeSubUnitShortName = $storeTransferModel->store_sub_unit_short_name;
            $referenceNumber = $storeTransferModel->reference_number;
            foreach ($stockTransferItemModel as $transactionItems) {
                // Receive Type is temporary
                $itemCode = $transactionItems['item_code'];
                $itemQuantityCount = $transactionItems['quantity'];
                $itemCategoryName = $transactionItems['item_category_name'];
                $itemDescription = $transactionItems['item_description'];
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
                $newStockLogModel->final_stock = $currentFinalStock - $itemQuantityCount;
                $newStockLogModel->transaction_type = 'out';
                $newStockLogModel->transaction_sub_type = 'transferred';
                $newStockLogModel->created_by_id = $createdById;
                $newStockLogModel->save();
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, __('msg.update_failed'), $exception->getMessage());
        }

    }
}
