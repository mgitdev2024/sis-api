<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use Illuminate\Http\Request;
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

        $response = \Http::get(config('apiurls.mgios.url') . config('apiurls.mgios.item_uom_get') . json_encode($itemCodes));
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
        $response = \Http::get(config('apiurls.mgios.url') . config('apiurls.mgios.item_uom_get') . json_encode($itemCodes));
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
            DB::beginTransaction();
            $storeCollection = StoreReceivingInventoryItemModel::select('store_code', 'store_sub_unit_short_name')
                ->distinct()
                ->get();

            foreach ($storeCollection as $store) {
                $storeCode = $store['store_code'];
                $storeSubUnit = $store['store_sub_unit_short_name'];
                $currentStockInventory = StockInventoryModel::where([
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnit
                ])->pluck('item_code');

                $toBeAddedItems = $this->onArrayDiffItems($storeSubUnit, $currentStockInventory->toArray());
                if (count($toBeAddedItems) > 0) {
                    $response = \Http::withHeaders([
                        'x-api-key' => config('apikeys.mgios_api_key'),
                    ])->post(config('apiurls.mgios.url') . config('apiurls.mgios.public_item_masterdata_collection_get'), [
                                'item_code_collection' => json_encode($toBeAddedItems),
                            ]);
                }
            }

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onArrayDiffItems($storeSubUnit, $existingItems)
    {
        $addedItems = [];

        if ($storeSubUnit == 'FOH') {
            $addedItems = ['EQ 6', 'CR 12', 'MM 6', 'BR 8', 'BR 4', 'FFG 8', 'FFG 4', 'LMS 8', 'LMS 4', 'AP WH', 'AP MN', 'TAS WH', 'TAS MN', 'VT WH', 'VT MN', 'CK WH', 'CC WH', 'CC MN', 'CH WH', 'CH MN', 'CM WH', 'CM MN', 'LI WH', 'MB WH', 'SSCV WH', 'SSCV MN', 'SW WH', 'TR WH', 'TR MN', 'BA WH', 'CTI WH', 'CTI MN', 'TROSE1', 'DCC1', 'TLC WH', 'AFFOG WH', 'FC LF', 'PU LF', 'BD LF', 'LGB J', 'LGQ J', 'IND7592', 'IND7594', 'IND7142', 'IND7141', 'IND7147', 'IND7144', 'IND7143', 'IND7145', 'IND7146', 'IND2039', 'IND1069', 'RMDD073', 'RMDD070', 'RMDD071', 'RMDD072', 'RMDD091', 'RMDD121', 'RMDD105', 'RMDD106', 'RMDD107', 'RMDD104', 'RMDD108', 'FG0055', 'FG0056', 'FG0057', 'FG0084', 'FG0053', 'RMDD022', 'RMDD021', 'RMDD062', 'FG0080', 'FG0061', 'FG0070', 'FG0074', 'FG0075', 'FG0085'];
        } else if ($storeSubUnit == 'BOH') {
            $addedItems = ['FG0005', 'FG0015', 'FG0020', 'FG0008', 'FG0007', 'FG0013', 'FG0017', 'FG0001', 'FG0018', 'FG0106', 'FG0010', 'FG0011', 'FG0006', 'FG0016', 'FG0002', 'FG0014', 'FG0019', 'FG0019', 'FG0050', 'FG0051', 'FG0003', 'FG0045', 'FG0046', 'FG0039', 'FG0077', 'FG0083', 'FG0082', 'FG0103', 'FG0104', 'FG0107', 'FG0105', 'FG0088', 'FG0040', 'FG0052', 'FG0090', 'FG0048', 'FG0102', 'RMDD009', 'FG0076', 'FG0086', 'RMDD030', 'RMDD042', 'RMDD055', 'RMDD031', 'RMDD018', 'TW025', 'FG0034', 'FG0012', 'FG0035', 'FG0037', 'FG0033', 'FG0036', 'FG0038', 'SF-BGU01', 'FG0032', 'FG0024', 'FG0022', 'FG0027', 'FG0030', 'FG0026', 'FG0099', 'FG0028', 'FG0021', 'FG0029', 'FG0025', 'FG0097', 'FG0023', 'FG0089', 'FG0091', 'FG0071', 'FG0062', 'FG0072', 'FG0064', 'FG0065', 'FG0063', 'TW003', 'TW029', 'TW001', 'RMDD097', 'FG0004', 'RMDD067', 'IND7076', 'IND7396', 'IND1055', 'IND1068', 'IND1111', 'IND1007', 'IND1118', 'IND1119', 'IND1014', 'RMDD063', 'RMDD061', 'RMDD003', 'FG0093', 'RMDD015', 'RMDD038', 'RMDD080', 'RMDD102', 'RMDD084', 'RMDD092', 'RMDD115', 'RMDD113', 'RMDD039', 'RMDD077', 'RMDD078', 'FG0067', 'FG0068', 'FG0069', 'FG0058', 'FG0059', 'FG0054', 'FG0066', 'FG0081', 'FG0092', 'RMDD004', 'FG0091', 'FG0100', 'RMDD069', 'RMDD049', 'RMDD050', 'RMDD048', 'RMDD051', 'RMDD045', 'RMDD100', 'RMDD094', 'RMDD026', 'RMDD001', 'RMDD008', 'RMDD044', 'RMDD043', 'RMDD058', 'FG0079', 'RMDD090', 'RMDD002', 'RMDD052', 'RMDD047', 'RMDD007', 'RMDD011', 'RMDD027', 'RMDD034', 'RMDD035', 'RMDD036', 'RMDD037', 'RMDD057', 'RMDD065', 'RMDD101', 'FG0047', 'TW005', 'TW009', 'TW006', 'TW007', 'TW010', 'TW016', 'TW018', 'RMDD010', 'RMDD114', 'RMDD023', 'TW019', 'TW017', 'TW020', 'TW021', 'TW022', 'TW026', 'TW027', 'RMDD005', 'RMDD059', 'RMDD060', 'RMDD029', 'RMDD054', 'RMDD089', 'FG0096', 'RMDD111', 'RMDD112', 'RMDD028', 'RMDD014', 'RMDD025', 'RMDD019', 'RMDD040', 'RMDD053', 'RMDD074', 'RMDD068'];
        }

        return array_values(array_diff($addedItems, $existingItems));
    }
}
