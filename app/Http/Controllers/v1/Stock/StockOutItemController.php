<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Models\Stock\StockOutItemModel;
use App\Models\Stock\StockOutModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class StockOutItemController extends Controller
{
    use ResponseTrait;
    public function onCreateStockOutItem($stockOutItems, $stockOutId, $createdById, $referenceNumber, $storeCode, $storeSubUnitShortName = null)
    {
        // [{"ic":"CR 12","idc":"Cheeseroll Box of 12","icn":"BREADS","icv":"Mini","uom":"Box","q":1}]
        try {
            DB::beginTransaction();
            foreach ($stockOutItems as $item) {
                $itemCode = $item['ic'];
                $itemDescription = $item['idc'];
                $itemCategoryName = $item['icn'];
                // $itemVariantName = $item['icv'];
                // $unitOfMeasure = $item['uom'];
                $quantity = $item['q'] ?? 0;

                StockOutItemModel::insert([
                    'stock_out_id' => $stockOutId,
                    'created_by_id' => $createdById,
                    'item_code' => $itemCode,
                    'item_description' => $itemDescription,
                    'item_category_name' => $itemCategoryName,
                    // 'item_variant_name' => $itemVariantName,
                    // 'unit_of_measure' => $unitOfMeasure,
                    'quantity' => $quantity,
                    'created_at' => now(),
                ]);

                // Update Stock Count

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
                $newStockLogModel->quantity = $quantity;
                $newStockLogModel->initial_stock = $currentFinalStock;
                $newStockLogModel->final_stock = $currentFinalStock - $quantity;
                $newStockLogModel->transaction_type = 'out';
                $newStockLogModel->transaction_sub_type = 'stock_out';
                $newStockLogModel->created_by_id = $createdById;
                $newStockLogModel->save();

                $stockInventoryModel = StockInventoryModel::where([
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'item_code' => $itemCode,
                ])->first();
                if ($stockInventoryModel) {
                    $stockInventoryModel->stock_count -= $quantity;
                    $stockInventoryModel->save();
                }
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception(__('msg.create_failed'), 400, $exception);
        }
    }

    public function onGet($stock_out_id = null)
    {
        try {
            if (!$stock_out_id) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }

            $stockOutModel = StockOutModel::findOrFail($stock_out_id);
            $subUnit = $stockOutModel->store_sub_unit_short_name;

            // Local items indexed by item_code
            $localItems = $stockOutModel->stockOutItems()
                ->get()
                ->keyBy('item_code');

            $itemCodes = $localItems->keys();

            $response = \Http::withHeaders([
                'x-api-key' => config('apikeys.mgios_api_key'),
            ])->post(
                    config('apiurls.mgios.url') . config('apiurls.mgios.public_get_item_by_department') . $subUnit,
                    ['item_code_collection' => json_encode($itemCodes)]
                );

            if (!$response->successful()) {
                return $this->dataResponse('error', 500, 'Failed to fetch item data from API');
            }

            $apiData = $response->json();

            // Merge local data into API structure
            foreach ($apiData as $department => &$categories) {
                foreach ($categories as $category => &$items) {
                    foreach ($items as &$item) {
                        $code = $item['item_code'];

                        if (isset($localItems[$code])) {
                            $local = $localItems[$code]->toArray();

                            $local['uom'] = $item['uom'] ?? null;

                            $item = $local;
                        }
                    }
                }
            }
            unset($categories, $items, $item);

            // Injected local data into api response
            $data = $stockOutModel->toArray();
            $data['stock_out_items'] = $apiData;

            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (\Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'));
        }
    }
}
