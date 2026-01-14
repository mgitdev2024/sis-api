<?php

namespace App\Jobs\Stock;

use App\Models\Stock\StockInventoryModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use Cache;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use DB;
use Exception;
class GenerateInitialStockItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \Log::info('Generation of initial stock items started.');
        $this->onGenerateInitialStockItems();
    }

    private function onGenerateInitialStockItems()
    {
        DB::beginTransaction();

        try {
            $storeCollection = Http::withHeaders([
                'x-api-key' => config('apikeys.sds_api_key'),
            ])->get(config('apiurls.sds.url') . config('apiurls.sds.public_store_list_get'));

            $storeCollection = $storeCollection->json()['success']['data'];
            // Retrieve cached MGIOS master data or initialize empty array
            $cacheKey = 'mgios_item_masterdata';
            $itemMasterData = Cache::get($cacheKey, []);

            foreach ($storeCollection as $store) {
                $storeCode = $store['code'];
                $firstChar = strtoupper(substr($storeCode, 0, 1));
                // if($storeCode != '82CA'  /*|| $storeCode != 'C085' || $storeCode != '82CA' || $storeCode != '86CA' */){
                //     continue;
                // }
                $storeSubUnitArr = ['FOH'];
                // if ($firstChar === 'C') {
                //     $storeSubUnitArr = ['FOH', 'BOH'];
                // }

                foreach ($storeSubUnitArr as $storeSubUnit) {
                    // Get existing stock items for the store
                    $currentStockInventory = StockInventoryModel::where([
                        'store_code' => $storeCode,
                        'store_sub_unit_short_name' => $storeSubUnit
                    ])->pluck('item_code');

                    // Determine missing items that are not yet in inventory
                    $toBeAddedItems = $this->onArrayDiffItems($storeSubUnit, $currentStockInventory->toArray());

                    if (count($toBeAddedItems) > 0) {
                        // Fetch missing items from MGIOS
                        $missingItems = array_diff($toBeAddedItems, array_keys($itemMasterData));

                        if (!empty($missingItems)) {
                            $response = Http::withHeaders([
                                'x-api-key' => config('apikeys.mgios_api_key'),
                            ])->post(config('apiurls.mgios.url') . config('apiurls.mgios.public_item_masterdata_collection_get'), [
                                        'item_code_collection' => json_encode($missingItems),
                                    ]);

                            if ($response->successful()) {
                                $newItems = $response->json();

                                // Add to cache
                                foreach ($newItems as $item) {
                                    $itemMasterData[$item['item_code']] = $item;
                                }

                                // Update cache (24 hours)
                                Cache::put($cacheKey, $itemMasterData, now()->addHours(24));
                            }
                        }

                        // Build insert data from cached + new data
                        $stockInventoryData = [];
                        foreach ($toBeAddedItems as $code) {
                            $item = $itemMasterData[$code] ?? null;
                            if (!$item)
                                continue; // Skip missing or invalid

                            $stockInventoryData[$code] = [
                                'store_code' => $storeCode,
                                'store_sub_unit_short_name' => $storeSubUnit,
                                'item_code' => $code,
                                'item_description' => $item['long_name'] ?? '',
                                'item_category_name' => $item['category_name'] ?? '',
                                'stock_count' => 0,
                                'status' => 1,
                                'created_by_id' => '0000',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        if (!empty($stockInventoryData)) {
                            // Bulk insert to reduce overhead
                            StockInventoryModel::insert(array_values($stockInventoryData));
                        }
                    }
                }
            }

            DB::commit();
            \Log::info('Initial inventory generation completed successfully.');
        } catch (\Throwable $exception) {
            DB::rollBack();
            \Log::info('Error generating initial inventory: ' . $exception->getMessage());
        }
    }

    private function onArrayDiffItems($storeSubUnit, $existingItems)
    {
    $addedItems = [];
        $cafeItems = [];
        if ($storeSubUnit == 'FOH') {
            $cafeItems[] = ["FG0075-1", "FG0080-1", "FG0070-1", "FG0059-1", "FG0061-1", "FG0058-1", "FG0053-1", "FG0074-1", "FG0055-1", "FG0056-1", "FG0057-1", "FG0084-1", "IND7625-1", "IND7495-1", "IND7620-1", "RMDD104-1", "RMDD106-1", "RMDD107-1", "RMDD108-1", "RMDD091-1", "RMDD061-1", "RMDD062-1", "RMDD063-1", "RMDD021-1", "RMDD023-1", "RMDD013-1", "RMDD022-1", "RMDD078-1", "RMDD115-1", "RMDD105", 1, 2, 3, 9, 10, 11, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 60, 61, 62, 200, 201, 202, 203, 204, 207, 208, 216, 217, 218, 219, 220, 341, 342, 343, 344, 345, 346, 347, 57, 209, 862, 861, 351, 352, 353, 354, 355, 356, 357, 358, 359, "IND1026", "IND1029", "IND1030", "IND1031", "IND1032", "IND1067", "IND1069", "IND1073", "IND1074", "IND1075", "IND1080", "IND1081", "IND1079", "IND1086", "IND1091", "IND1109", "IND1115", "IND7141", "IND7142", "IND7143", "IND7144", "IND7145", "IND7146", "IND7147", "IND1084", "IND1082", "IND1083", "IND7070", "IND2039", "IND2040", "IND2045", "IND2048", "IND2060", "IND2049", "IND4008", "IND3004", "IND7119", "IND3012", "IND3005", "IND7080", "IND6120", "IND6025", "IND6026", "IND6027", "IND6028", "IND6029", "IND6030", "IND6031", "IND6032", "IND6033", "IND6034", "IND6035", "IND6044", "IND7006", "IND6047", "IND6048", "IND7007", "IND7008", "IND6072", "IND7014", "IND6075", "IND6076", "IND6078", "IND6079", "IND6080", "IND6088", "IND7016", "IND6093", "IND7020", "IND7021", "IND7022", "IND7023", "IND7024", "IND7025", "IND6111", "IND6112", "IND6125", "IND7034", "IND7035", "IND6128", "IND6130", "IND6136", "IND7042", "IND7043", "IND6145", "IND7046", "IND7048", "IND7049", "IND6149", "IND7004", "IND6077", "IND7081", "IND7082", "IND7120", "IND7121", "IND7125", "IND7130", "IND7131", "IND7153", "IND7154", "IND7159", "IND7358", "IND7362", "IND7065", "IND7148", "IND2050", "IND2054", "IND2055", "IND4027", "IND7097", "IND3031", "IND4003", "IND4004", "IND4006", "IND4007", "IND4018", "IND4028", "IND4033", "IND4015", "IND4014", "IND4019", "IND4021", "IND4022", "IND4023", "IND4024", "IND4025", "IND4026", "IND4027", "IND4030", "IND4031", "IND4034", "IND4029", "IND3003", "IND5002", "IND5020", "IND5045", "IND5046", "IND5024", "IND5048", "IND5068", "IND5036", "IND5038", "IND5040", "IND5041", "IND5043", "IND5044", "IND5066", "IND5070", "IND5053", "IND5054", "IND5004", "IND5005", "IND5028", "IND5029", "IND5031", "IND7096", "IND5034", "IND5035", "IND5055", "IND5056", "IND5057", "IND5058", "IND5078", "IND7101", "IND7118", "IND6079", "IND5076", "DST", "DST- GREEN", "D. TAPE", "ELMERS", "STICK PASTE", "TAPE_BIG", "TAPE_SMALL", "TAPE_BROWN", "TAPE_CLEAR", "S_TAPE", "FAST.", "SCIS", "SHARP_SMALL", "STAMP_BLACK", "STAMP_BLUE", "STAMP_CLEAR", "STAMP_INKBLK", "STAMP_INKBL", "STAPL_FREE", "B.ENVE LONG", "B.ENVE SHORT", "EXP. ENVE. LONG", "EXP. ENVE. SHORT", "PLAST_LONG", "PLAST_SHORT", "WHITE_ENVE", "CLBOOK BLUE", "CLBOOK RED", "CLB REF. LONG", "CLB REF. SHORT", "FIRE BLANKET", "FIRE EX BN (AFFF)", "FIRE EX BN (HCFC)", "PT_BLUE", "PT_GREEN", "PT_PINK", "PT_WHITE", "MZN", "P.COVER", "WBOOK", "CARBO", "C.VOUCHER .5", "C.VOUCHER .25", "C.C.5", "C.VOUCHER C.25", "M_PAPER", "NBO", "NBV", "R_BOOK 500", "PST_MED", "PST_SMALL", "TRANS1001", "ILT", "IWATA", "CALC", "D. GUN", "IWATA_BULB", "MDETECTOR", "PUNCH", "TAPE_DISP.", "BAT_AA", "BAT_AAA", "TPS0334", "MAYA_THERMAL", "MAG_N.PLATES", "MG_PIN", "COR. TAPE", "CRA_CAF", "E. PENCIL", "ADM001", "ADM002", "H. LBLUE", "H. LGREEN", "H. LPINK", "H. LYELLOW", "PENCIL", "PERMA_BLACK", "PERMA_BLUE", "PERMA_RED", "PERMA_INKBLK", "PERMA_INK BLUE", "PERMA_INK RED", "WHITEBRD_BLK", "WHITEBRD_BL", "WHITEBRD_RED", "WHITEBRD_INKBLK", "WHITEBRD_INKBL", "WHITEBRD_INKRED", "RMDD070", "RMDD071", "RMDD072", "RMDD073", "RMDD121","RMDD075-1","IND7592","IND1026-1","IND1030-1","IND1029-1","IND1031-1","IND1032-1","IND1067-1","IND2039-1","ADM005","F. SHORT","F. LONG", "IND7625", "IND7495", "IND7620", "FG0075", "FG0080", "FG0070", "FG0059", "FG0061", "FG0058", "FG0053", "FG0074", "FG0055", "FG0056", "FG0057", "FG0084", "RMDD104", "RMDD106", "RMDD107", "RMDD108", "RMDD091", "RMDD061", "RMDD062", "RMDD063", "RMDD021", "RMDD023", "RMDD013", "RMDD022", "RMDD078", "RMDD115", "RMDD105", "IND7636", "IND7637", "24884", "24885", "24804", "24883", "24879", "05367", "05368"];
        } else if ($storeSubUnit == 'BOH') {
            $cafeItems[] = ["FG0002", "FG0003", "FG0004", "FG0005", "FG0006", "FG0007", "FG0008", "FG0009", "FG0010", "FG0011", "FG0012", "FG0013", "FG0014", "FG0015", "FG0016", "FG0017", "FG0018", "FG0019", "FG0020", "FG0021", "FG0022", "FG0023", "FG0024", "FG0025", "FG0026", "FG0027", "FG0028", "FG0029", "FG0030", "FG0032", "FG0033", "FG0034", "FG0035", "FG0036", "FG0037", "FG0038", "FG0039", "FG0040", "FG0045", "FG0046", "FG0048", "FG0050", "FG0051", "FG0052", "FG0062", "FG0063", "FG0064", "FG0065", "FG0071", "FG0072-1", "FG0086", "FG0088", "FG0089", "FG0090", "FG0097", "FG0099", "FG0101", "FG0102", "FG0103", "FG0104", "FG0105", "FG0106", "FG0107", "FG0110", "IND1014-1", "IND1015-1", "IND1055-1", "IND1068-1", "IND1087-1", "IND1111-1", "IND1118-1", "IND7076-1", "IND7226", "IND7408-1", "IND7408-1", "FG0076", "FG0077", "RMDD009-1", "RMDD030", "RMDD031-1", "RMDD042-1", "RMDD055-1", "RMDD067-1", "RMDD097-1", "TW001-1", "TW003-1", "TW029-1", "FG0098", "FG0031", "FG0047", "FG0054", "FG0068", "FG0067", "FG0069", "FG0081", "RMDD103", "RMDD012", "RMDD002", "RMDD003", "RMDD004", "RMDD005", "RMDD007", "RMDD008", "RMDD011", "RMDD018", "RMDD026", "RMDD027", "RMDD034", "FG0085", "FG0091", "FG0092", "FG0093", "FG0096", "RMDD035", "RMDD036", "RMDD037", "RMDD038", "RMDD039", "RMDD043", "RMDD046", "RMDD047", "RMDD048", "RMDD049", "RMDD050", "RMDD051", "RMDD054", "RMDD057", "RMDD058", "RMDD059", "RMDD060", "RMDD065", "RMDD080", "RMDD084", "RMDD089", "RMDD092", "RMDD100", "RMDD101", "RMDD111", "RMDD112", "RMDD044", "RMDD102", "RMDD014", "RMDD017", "RMDD019", "RMDD025", "RMDD028", "RMDD040", "TW004", "TW006", "TW007", "TW009", "TW016", "TW017", "TW018", "TW019", "TW021", "TW022", "TW023", "TW025", "RMDD053", "RMDD068", "RMDD074", "RMDD075-1", "RMDD079", "IND1120", "IND1121", "IND1122", "IND1004", "IND1054", "IND1124", "IND1119", "IND1007", "IND3008", "IND6001", "IND6002", "IND6003", "IND6004", "IND7001", "IND7002", "IND6005", "IND6007", "IND6008", "IND6010", "IND6011", "IND6012", "IND6013", "IND6014", "IND6015", "IND6016", "IND6017", "IND6018", "IND6019", "IND6020", "IND6021", "IND6022", "IND6023", "IND6086", "IND6037", "IND6038", "IND6039", "IND6040", "IND6041", "IND6042", "IND6043", "IND6046", "IND6050", "IND7010", "IND7011", "IND6052", "IND6053", "IND6054", "IND6055", "IND6056", "IND6057", "IND6058", "IND6059", "IND6060", "IND6061", "IND6062", "IND6063", "IND6064", "IND6065", "IND6066", "IND6067", "IND6068", "IND6069", "IND6070", "IND6071", "IND6073", "IND6074", "IND6081", "IND6082", "IND6083", "IND6084", "IND6085", "IND6087", "IND6094", "IND6095", "IND6096", "IND6097", "IND6098", "IND6099", "IND6100", "IND6101", "IND7019", "IND6103", "IND6104", "IND7026", "IND6105", "IND6106", "IND6107", "IND6108", "IND6109", "IND7027", "IND7029", "IND6113", "IND6114", "IND6115", "IND6117", "IND6118", "IND7072", "IND6122", "IND6123", "IND6124", "IND7031", "IND7032", "IND6127", "IND7037", "IND7038", "IND7039", "IND6129", "IND6133", "IND7040", "IND7041", "IND6140", "IND6141", "IND6142", "IND6143", "IND6144", "IND7044", "IND7045", "IND7050", "IND7051", "IND7052", "IND7053", "IND7054", "IND6146", "IND6147", "IND6148", "IND6150", "IND7134", "IND6089", "IND6090", "IND7083", "IND7084", "IND7085", "IND6161", "IND7087", "IND6036", "IND7091", "IND7092", "IND7123", "IND7124", "IND7133", "IND7139", "IND7149", "IND7150", "IND7151", "IND7152", "IND7158", "IND7162", "IND7168", "IND7171", "IND7203", "IND7366", "IND7376", "IND7521", "IND6141", "IND7560", "IND7151", "IND4020", "IND4032", "IND5021", "IND5023", "IND5060", "IND5061", "IND5062", "IND5063", "IND5064", "IND5059", "IND5069", "IND5015", "IND7077", "IND7078", "IND7079", "IND5026", "IND5037", "IND5042", "IND5049", "IND5050", "IND5051", "IND5013", "IND5030", "IND5033", "IND5047", "IND5019", "IND5022", "IND5008", "IND5007", "IND5012", "IND5047", 5317, 5318, 5319, 5320, 5321, 5322, 5323, 5324, 5325, 5326, 5327, 5328, 5329, 5330, 5331, 5332, 5207, 5063, 5144, 5200, 5160, 5157, "FG0083", "FG0082", "TW020", "RMDD090", "RMDD117", "RMDD001", "RMDD029", "RMDD052", "FG0079-1", "RMDD032-1", "RMDD077-1", "FG0066-1", "TW026-1", "TW027-1", "RMDD015-1", "RMDD118-1", "TW010-1", "TW005-1", "RMDD069-1", "IND2038-1", "FG0002-B10", "FG0003-B10", "FG0004-B10", "FG0005-B10", "FG0006-B10", "FG0007-B10", "FG0008-B10", "FG0009-B10", "FG0010-B10", "FG0011-B10", "FG0012-B10", "FG0013-B10", "FG0014-B10", "FG0015-B10", "FG0016-B10", "FG0017-B10", "FG0018-B10", "FG0019-B10", "FG0020-B10", "FG0021-B10", "FG0022-B10", "FG0023-B20", "FG0024-B10", "FG0025-B10", "FG0026-B10", "FG0027-B10", "FG0028-B10", "FG0029-B4", "FG0030-B10", "FG0032-B10", "FG0033-B10", "FG0034-B10", "FG0035-B10", "FG0036-B5", "FG0037-B10", "FG0038-B5", "FG0039-B20", "FG0040-B10", "FG0045-B10", "FG0046-B10", "FG0048-B10", "FG0050-B5", "FG0051-B10", "FG0052-B10", "FG0062-B20", "FG0063-B10", "FG0064-B10", "FG0065-B10", "FG0071-B5", "FG0072", "FG0086-B5", "FG0088-B10", "FG0089-B20", "FG0090-B10", "FG0097-B5", "FG0099-B10", "FG0101-B8", "FG0102-B10", "FG0103-B10", "FG0104-B10", "FG0105-B10", "FG0106-B10", "FG0107-B10", "FG0110-B10", "RMDD009", "RMDD030-B30", "RMDD031", "RMDD042", "RMDD055", "RMDD067", "RMDD097", "RMDD018", "TW001", "TW003", "TW029", "TW025", "IND1014", "IND1015", "IND1055", "IND1068", "IND1087", "IND1111", "IND1118", "IND7076", "IND7226", "IND7408", "RMDD075", "FG0079", "RMDD032", "RMDD077", "FG0066", "TW026", "TW027", "RMDD015", "RMDD118", "TW010", "TW005", "RMDD069", "IND2038", "RMDD032", "FG0066", "RMDD077"];
        }
        if (count($cafeItems) > 0) {
            $addedItems = array_merge($addedItems, $cafeItems[0]);
        }
        return array_values(array_diff($addedItems, $existingItems));
    }
}
