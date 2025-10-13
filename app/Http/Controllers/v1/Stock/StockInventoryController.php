<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Jobs\Stock\GenerateInitialStockItemsJob;
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

    public function onGet($is_group, $store_code, $sub_unit = null)
    {
        try {
            $stockInventoryModel = StockInventoryModel::where('store_code', $store_code);
            if ($sub_unit != null) {
                $stockInventoryModel->where('store_sub_unit_short_name', $sub_unit);
            }
            $stockInventoryModel = $stockInventoryModel->orderBy('status', 'DESC')->orderBy('item_code', 'ASC')->get();

            if ($is_group == 1) {
                $stockInventoryModel = $stockInventoryModel->groupBy('item_category_name');
                $this->getUomByGroup($stockInventoryModel);
            } else {
                $this->getUom($stockInventoryModel);
            }
            if (count($stockInventoryModel) <= 0) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'), null);
            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $stockInventoryModel);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function getUomByGroup($stockInventoryData)
    {
        $itemCodes = $stockInventoryData
            ->flatMap(function ($items) {
                return collect($items)->pluck('item_code');
            })
            ->values()
            ->all();

        $response = Http::withHeaders([
            'x-api-key' => config('apikeys.mgios_api_key')
        ])->post(config('apiurls.mgios.url') . config('apiurls.mgios.public_item_uom_get'), [
                    'item_code_collection' => json_encode($itemCodes)
                ]);
        $uomData = collect($response->json()); // make uomData a collection for easier lookup

        $stockInventoryData = $stockInventoryData->map(function ($items) use ($uomData) {
            return collect($items)->map(function ($item) use ($uomData) {
                $itemCode = $item['item_code'];
                $uom = $uomData[$itemCode] ?? null;
                $item['uom'] = $uom;
                return $item;
            });
        });
    }

    public function getUom($stockInventoryData)
    {
        $itemCodes = collect($stockInventoryData)
            ->pluck('item_code')
            ->unique()
            ->values()
            ->all();
        $response = Http::withHeaders([
            'x-api-key' => config('apikeys.mgios_api_key')
        ])->post(config('apiurls.mgios.url') . config('apiurls.mgios.public_item_uom_get'), [
                    'item_code_collection' => json_encode($itemCodes)
                ]);
        $uomData = collect($response->json()); // make uomData a collection for easier lookup

        $stockInventoryData = collect($stockInventoryData)->map(function ($item) use ($uomData) {
            $itemCode = $item['item_code'];
            $item['uom'] = $uomData[$itemCode] ?? null;
            return $item;
        });

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
}
