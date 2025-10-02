<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryCountModel;
use App\Models\Stock\StockInventoryItemCountModel;
use App\Models\Stock\StockInventoryModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use DB;
class StockInventoryCountController extends Controller
{
    use ResponseTrait, CrudOperationsTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'type' => 'required|in:1,2,3', // 1 = Hourly, 2 = EOD, 3 = Month-End
            'store_code' => 'required',
            'store_sub_unit_short_name' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];

            $hasPending = StockInventoryCountModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName
            ])->whereIn('status', [0, 1])->exists();

            if ($hasPending) {
                return $this->dataResponse('error', 400, 'Still has pending stock count');
            }
            $referenceNumber = StockInventoryCountModel::onGenerateReferenceNumber();
            $type = $fields['type'];

            $stockCountDate = now();

            $response = \Http::withHeaders([
                'x-api-key' => config('apikeys.scm_api_key'),
            ])->get(config('apiurls.scm.url') . config('apiurls.scm.public_stock_count_lead_time_current_get'));

            if ($response->successful()) {
                $leadTime = $response->json()['success']['data'] ?? [];
                $leadTimeFrom = $leadTime['lead_time_from'] ?? null;
                $leadTimeTo = $leadTime['lead_time_to'] ?? null;
                $currentTime = now()->format('H:i:s');

                if (
                    Carbon::createFromTimeString($currentTime)
                        ->between(
                            Carbon::createFromTimeString($leadTimeFrom),
                            Carbon::createFromTimeString($leadTimeTo)
                        )
                ) {
                    $stockCountDate = now()->subDay(); // yesterday
                }
            }

            $stockInventoryCount = StockInventoryCountModel::create([
                'reference_number' => $referenceNumber,
                'type' => $type, // 1 = Hourly, 2 = EOD, 3 = Month-End
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'created_by_id' => $createdById,
                'updated_by_id' => $createdById,
                'status' => 0,
                'created_at' => $stockCountDate
            ]);
            $stockInventoryCount->save();

            $this->onCreateStockInventoryItemsCount($stockInventoryCount->id, $storeCode, $storeSubUnitShortName, $createdById);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onCreateStockInventoryItemsCount($stockInventoryCountId, $storeCode, $storeSubUnitShortName, $createdById)
    {
        try {
            $existingItemCodes = [];
            $stockInventoryModel = StockInventoryModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
            ])->orderBy('item_code', 'DESC')->get();

            $stockInventoryItemsCount = [];
            foreach ($stockInventoryModel as $item) {
                $existingItemCodes[] = $item->item_code;
                $stockInventoryItemsCount[] = [
                    'stock_inventory_count_id' => $stockInventoryCountId,
                    'item_code' => $item->item_code,
                    'item_description' => $item->item_description,
                    'item_category_name' => $item->item_category_name,
                    'system_quantity' => $item->stock_count,
                    'counted_quantity' => 0,
                    'discrepancy_quantity' => 0,
                    'created_at' => now(),
                    'created_by_id' => $createdById,
                    'updated_by_id' => $createdById,
                    'status' => 1, // For Receive
                ];
            }
            $toBeAddedItems = [];
            if (strcasecmp($storeSubUnitShortName, 'BOH') === 0) {
                $toBeAddedItems = $this->BohItems($existingItemCodes);
            } else {
                $toBeAddedItems = $this->FohItems($existingItemCodes);
            }
            if (count($toBeAddedItems) > 0) {
                $response = \Http::withHeaders([
                    'x-api-key' => config('apikeys.mgios_api_key'),
                ])->post(config('apiurls.mgios.url') . config('apiurls.mgios.public_item_masterdata_collection_get'), [
                            'item_code_collection' => json_encode($toBeAddedItems),
                        ]);

                $data = $response->json() ?? [];
                if (!empty($data)) {
                    foreach ($data as $item) {
                        $stockInventoryItemsCount[] = [
                            'stock_inventory_count_id' => $stockInventoryCountId,
                            'item_code' => $item['item_code'],
                            'item_description' => $item['long_name'],
                            'item_category_name' => $item['category_name'],
                            'system_quantity' => 0,
                            'counted_quantity' => 0,
                            'discrepancy_quantity' => 0,
                            'created_at' => now(),
                            'created_by_id' => $createdById,
                            'updated_by_id' => $createdById,
                            'status' => 1, // For Receive
                        ];
                    }
                }
            }
            StockInventoryItemCountModel::insert($stockInventoryItemsCount);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function FohItems($existingItemCodes)
    {
        $fohItems = [
            "EQ 6",
            "CR PCS",
            "CR 12",
            "MM 6",
            "BR 8",
            "BR 4",
            "FFG 8",
            "FFG 4",
            "LMS 8",
            "LMS 4",
            "AP WH",
            "AP MN",
            "TAS WH",
            "TAS MN",
            "VT WH",
            "VT MN",
            "CK WH",
            "CC WH",
            "CC MN",
            "CH WH",
            "CH MN",
            "CM WH",
            "CM MN",
            "LI WH",
            "MB WH",
            "SSCV WH",
            "SSCV MN",
            "SW WH",
            "TR WH",
            "TR MN",
            "BA WH",
            "CTI WH",
            "CTI MN",
            "TROSE1",
            "DCC1",
            "TLC WH",
            "AFFOG WH",
            "PU LF",
            "BD LF",
            "LGB J",
            "LGQ J",
            "IND7592",
            "IND7594",
            "IND7142",
            "IND7141",
            "IND7147",
            "IND7144",
            "IND7143",
            "IND7145",
            "IND7146",
            "IND2039",
            "IND1069",
            "RMDD073",
            "RMDD070",
            "RMDD121",
            "RMDD071",
            "RMDD072",
            "RMDD091",
            "RMDD105-B10",
            "RMDD106-B10",
            "RMDD107-B10",
            "RMDD104-B10",
            "RMDD108-B10",
            "FG0055",
            "FG0056",
            "FG0057",
            "FG0084",
            "FG0053",
            "RMDD022",
            "RMDD021",
            "RMDD062",
            "FG0080",
            "FG0061",
            "FG0070",
            "FG0074",
            "FG0075",
            "FG0085",

            // ⬇️ Newly added items
            "EQ PCS",
            "CR PCS",
            "MM PCS",

            "BR SL",
            "LMS PC",
            "VT SL",
            "CK SL",
            "CC SL",
            "CH SL",
            "SSCV SL",
            "SW SL",
            "TR SL",
            "CM SL",

            "MB DD",
            "MB SL",
            "CTI SL",
            "PU SL",
            "BD SL",
            "FC SL",
            "LGB",
            "LGQB",

            "FC LF",
            "IND1050",
            "IND1051"
        ];

        return array_values(array_diff($fohItems, $existingItemCodes));
    }

    public function BohItems($existingItemCodes)
    {
        $bohItems = [
            "FG0009-B10",
            "FG0005-B10",
            "FG0015-B10",
            "FG0020-B10",
            "FG0008-B10",
            "FG0007-B10",
            "FG0013-B10",
            "FG0017-B10",
            "FG0001-B6",
            "FG0018-B10",
            "FG0106-B10",
            "FG0010-B10",
            "FG0011-B10",
            "FG0006-B10",
            "FG0016-B10",
            "FG0002-B10",
            "FG0014-B10",
            "FG0019-B10",
            "FG0050-B5",
            "FG0051-B10",
            "FG0003-B10",
            "FG0045-B10",
            "FG0046-B10",
            "FG0039-B20",
            "FG0077-B100",
            "FG0083",
            "FG0082",
            "FG0103-B10",
            "FG0104-B10",
            "FG0107-B10",
            "FG0105-B10",
            "FG0088-B10",
            "FG0040-B10",
            "FG0052-B10",
            "FG0090-B10",
            "FG0048-B10",
            "FG0102-B10",
            "RMDD009",
            "FG0076-B50",
            "FG0086-B5",
            "RMDD030-B30",
            "RMDD042",
            "RMDD055",
            "RMDD031",
            "RMDD018",
            "TW025",
            "FG0034-B10",
            "FG0012-B10",
            "FG0035-B10",
            "FG0037-B10",
            "FG0033-B10",
            "FG0036-B5",
            "FG0038-B5",
            "SF-BGU01",
            "FG0032-B10",
            "FG0024-B10",
            "FG0022-B10",
            "FG0027-B10",
            "FG0030-B10",
            "FG0026-B10",
            "FG0099-B10",
            "FG0028-B10",
            "FG0021-B10",
            "FG0029-B4",
            "FG0025-B10",
            "FG0097-B5",
            "FG0023-B20",
            "FG0089-B20",
            "FG0091",
            "FG0071",
            "FG0062-B20",
            "FG0072",
            "FG0064-B10",
            "FG0065-B10",
            "FG0063-B10",
            "TW003",
            "TW029",
            "TW001",
            "RMDD097",
            "FG0004-B10",
            "RMDD067",
            "IND7076",
            "IND7396",
            "IND1055",
            "IND1068",
            "IND1111",
            "IND1007",
            "IND1118",
            "IND1119",
            "IND1014",
            "RMDD063",
            "RMDD061",
            "RMDD003",
            "FG0093",
            "RMDD015",
            "RMDD038",
            "RMDD080",
            "RMDD102",
            "RMDD084",
            "RMDD092",
            "RMDD115",
            "RMDD113",
            "RMDD039",
            "RMDD077",
            "RMDD078",
            "FG0067",
            "FG0068",
            "FG0069",
            "FG0058",
            "FG0059",
            "FG0054",
            "FG0066",
            "FG0081",
            "FG0092",
            "RMDD004",
            "FG0091",
            "FG0100",
            "RMDD069",
            "RMDD049",
            "RMDD050",
            "RMDD048",
            "RMDD051",
            "RMDD045",
            "RMDD100",
            "RMDD094",
            "RMDD026",
            "RMDD001",
            "RMDD008",
            "RMDD044",
            "RMDD043",
            "RMDD058",
            "FG0079",
            "RMDD090",
            "RMDD002",
            "RMDD052",
            "RMDD047",
            "RMDD007",
            "RMDD011",
            "RMDD027",
            "RMDD034",
            "RMDD035",
            "RMDD036",
            "RMDD037",
            "RMDD057",
            "RMDD065",
            "RMDD101",
            "FG0047",
            "TW005",
            "TW009",
            "TW006",
            "TW007",
            "TW010",
            "TW016",
            "TW018",
            "RMDD010",
            "RMDD114",
            "RMDD023",
            "TW019",
            "TW017",
            "TW020",
            "TW021",
            "TW022",
            "TW026",
            "TW027",
            "RMDD005",
            "RMDD059",
            "RMDD060",
            "RMDD029",
            "RMDD054",
            "RMDD089",
            "FG0096",
            "RMDD111",
            "RMDD112",
            "RMDD028",
            "RMDD014",
            "RMDD025",
            "RMDD019",
            "RMDD040",
            "RMDD053",
            "RMDD074",
            "RMDD068",

            // Newly added items
            'IND1060',
            'IND2039',
            'IND2040'
        ];

        return array_values(array_diff($bohItems, $existingItemCodes));
    }

    public function onGet($status, $store_code, $sub_unit = null)
    {
        try {
            $stockInventoryCountModel = StockInventoryCountModel::where('store_code', $store_code);
            if ($status == 0) {
                $stockInventoryCountModel->whereIn('status', [0, 1]);
            } else if ($status == 1) {
                $stockInventoryCountModel->where('status', 2);
            }
            if ($sub_unit) {
                $stockInventoryCountModel->where('store_sub_unit_short_name', $sub_unit);
            }
            $stockInventoryCountModel = $stockInventoryCountModel->orderBy('id', 'DESC')->get();
            return $this->dataResponse('success', 200, __('msg.record_found'), $stockInventoryCountModel);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'), $exception->getMessage());
        }

    }
    public function onCancel(Request $request, $store_inventory_count_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $stockInventoryCountModel = StockInventoryCountModel::whereIn('status', [0, 1])->find($store_inventory_count_id);
            if (!$stockInventoryCountModel) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }
            $stockInventoryCountModel->status = 3; // Set status to Cancelled
            $stockInventoryCountModel->updated_by_id = $createdById;
            $stockInventoryCountModel->save();
            DB::commit();
            return $this->dataResponse('success', 200, 'Cancelled Successfully');
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Cancel Failed', $exception->getMessage());
        }
    }
}

