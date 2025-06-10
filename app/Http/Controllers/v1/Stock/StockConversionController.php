<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockConversionItemModel;
use App\Models\Stock\StockConversionModel;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
use DB;
class StockConversionController extends Controller
{
    use ResponseTrait;

    public function onCreate(Request $request, $stockInventoryId)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'total_converted_quantity' => 'required|numeric|min:1',
            'conversion_items' => 'required|json', // [{"ic":"CR PC","q":1,"cq":5},{"ic":"CR 6","q":2,"cq":5}]
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $stockInventoryModel = StockInventoryModel::findOrFail($stockInventoryId);
            $storeCode = $stockInventoryModel->store_code;
            $storeSubUnitShortName = $stockInventoryModel->store_sub_unit_short_name ?? null;
            $itemCode = $stockInventoryModel->item_code;
            $conversionItems = json_decode($fields['conversion_items'], true);
            $convertedQuantity = $fields['converted_quantity'];

            $referenceNumber = StockConversionModel::onGenerateReferenceNumber();
            $stockConversionModel = StockConversionModel::create([
                'reference_number' => $referenceNumber,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'item_code' => $itemCode,
                'item_description' => $stockInventoryModel->item_description,
                'item_category_name' => $stockInventoryModel->item_category_name,
                'quantity' => $convertedQuantity,
                'created_by_id' => $createdById,
            ]);
            $stockConversionId = $stockConversionModel->id;

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
                'stock_count' => $convertedItemStockInventoryModel->stock_count - $convertedQuantity,
                'updated_by_id' => $createdById,
                'updated_at' => now(),
            ]);

            foreach ($conversionItems as $items) {
                $convertedItemCode = $items['ic'];
                $quantity = $items['q'];
                $convertedQuantity = $items['cq'];
                if ($convertedQuantity <= 0 || $quantity <= 0) {
                    continue; // Skip if the converted quantity or quantity is not valid
                }

                $itemMasterData = \Http::get(env('SCM_URL') . '/item/masterdata/item-code/get/' . $convertedItemCode);
                if ($itemMasterData->status() != 200) {
                    continue;
                }
                $itemMasterData = $itemMasterData->json()['success']['data'] ?? [];
                $itemDescription = $itemMasterData['description'] ?? '';
                $itemCategoryName = $itemMasterData['category_name'] ?? '';


                StockConversionItemModel::insert([
                    'stock_conversion_id' => $stockConversionId,
                    'item_code' => $convertedItemCode,
                    'item_description' => $itemDescription,
                    'item_category_name' => $itemCategoryName,
                    'quantity' => $quantity,
                    'converted_quantity' => $convertedQuantity,
                    'created_by_id' => $createdById,
                ]);

                $this->onAdjustInventory($storeCode, $storeSubUnitShortName, $convertedItemCode, $quantity, $itemDescription, $itemCategoryName, $referenceNumber, $createdById);
            }
            DB::commit();
            return $this->dataResponse('error', 400, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onAdjustInventory($storeCode, $storeSubUnitShortName, $itemCode, $quantity, $itemDescription, $itemCategoryName, $referenceNumber, $createdById)
    {
        try {
            $stockInventoryModel = StockInventoryModel::where([
                'store_code' => $storeCode,
                'item_code' => $itemCode,
            ]);
            if ($storeSubUnitShortName != null) {
                $stockInventoryModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $stockInventoryModel = $stockInventoryModel->first();

            if ($stockInventoryModel) {
                $stockInventoryModel->update([
                    'stock_count' => $stockInventoryModel->stock_count + $quantity,
                    'updated_by_id' => $createdById,
                    'updated_at' => now(),
                ]);
            } else {
                StockInventoryModel::create([
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'item_code' => $itemCode,
                    'item_description' => $itemDescription,
                    'item_category_name' => $itemCategoryName,
                    'stock_count' => $quantity,
                    'created_by_id' => $createdById,
                ]);
            }

            // Stock Logs
            $stockLogModel = StockLogModel::where([
                'store_code' => $storeCode,
                'item_code' => $itemCode,
            ]);
            if ($storeSubUnitShortName != null) {
                $stockLogModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $stockLogModel = $stockLogModel->first();

            $initialStock = 0;
            if ($stockLogModel) {
                $initialStock = $stockLogModel->final_stock;
            }
            $finalStock = $quantity + $initialStock;

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
        } catch (Exception $exception) {
            throw new Exception($$exception->getMessage());
        }
    }
}
