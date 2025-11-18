<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Jobs\Stock\GenerateInitialStockItemsJob;
use App\Jobs\Stock\SyncItemListJob;
use App\Models\Stock\StockInventoryModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Traits\ResponseTrait;
use DB;

class StockInventoryController extends Controller
{
    use ResponseTrait;

    public function onGet($store_code, $sub_unit)
    {
        try {
            $stockinventoryModel = StockInventoryModel::where([
                'store_code' => $store_code,
                'store_sub_unit_short_name' => $sub_unit,
                'is_sis_variant' => 0
            ])->get()->keyBy('item_code');
            $itemCodes = $stockinventoryModel->keys();

            $response = Http::withHeaders([
                'x-api-key' => config('apikeys.mgios_api_key'),
            ])->post(
                config('apiurls.mgios.url') . config('apiurls.mgios.public_get_item_by_department') . $sub_unit,
                ['item_code_collection' => json_encode($itemCodes)]
            );

            if (!$response->successful()) {
                return $this->dataResponse('error', 500, 'Failed to fetch item data from API');
            }

            $apiData = $response->json();
            // 3️⃣ Merge local values into the nested API data (retain structure)
            foreach ($apiData as $department => &$categories) {
                foreach ($categories as $category => &$items) {
                    foreach ($items as &$item) {
                        $apiItemData = $item;
                        $code = $item['item_code'];
                        if (isset($stockinventoryModel[$code])) {
                            $local = $stockinventoryModel[$code];
                            $item = $local;
                            $item['uom'] = $apiItemData['uom'] ?? null;
                        }
                    }
                }
            }
            unset($categories, $items, $item);
            return $this->dataResponse('success', 200, __('msg.record_found'), $apiData);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetById($stockInventoryId = null)
    {
        try {
            if ($stockInventoryId) {
                $stockInventoryModel = StockInventoryModel::findOrFail($stockInventoryId);
                $itemCode = $stockInventoryModel->item_code;

                $response = \Http::get(config('apiurls.scm.url') . config('apiurls.scm.stock_conversion_item_id_get') . $itemCode);
                $apiResponse = $response->json()['success']['data'] ?? [];

                $stockConversionItem = $apiResponse['stock_conversion_items'] ?? [];

                $data = [
                    'stock_inventory' => $stockInventoryModel,
                    'stock_conversion_items' => []
                ];
                foreach ($stockConversionItem as $conversionItem) {
                    $itemCode = $conversionItem['item_code_label'];
                    $itemDescription = $conversionItem['item_masterdata']['description'] ?? '';
                    $itemVariant = $conversionItem['item_masterdata']['uom_label']['long_name'] ?? '';

                    $quantity = $conversionItem['quantity'] ?? 0;
                    $isDod = $conversionItem['is_dod'] ?? 0;

                    $data['stock_conversion_items'][] = [
                        'item_label' => $isDod == 1 ? "$itemCode (DOD)" : $itemCode,
                        'item_code' => $itemCode,
                        'item_description' => $isDod == 1 ? "$itemDescription (DOD)" : $itemDescription,
                        'item_variant' => $itemVariant,
                        'quantity' => $quantity,
                        'is_dod' => $isDod
                    ];
                }
                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            }

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGenerateInitialInventory()
    {
        try {
            // Dispatch background job instead of running everything in request
            GenerateInitialStockItemsJob::dispatch();
            return $this->dataResponse('success', 200, 'Initial inventory generation started in background.');
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onSyncItemList()
    {
        try {
            // Dispatch background job instead of running everything in request
            SyncItemListJob::dispatch();
            return $this->dataResponse('success', 200, 'Item list sync started in background.');
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }
}
