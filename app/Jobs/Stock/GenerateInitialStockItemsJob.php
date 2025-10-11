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
            $storeCollection = StockInventoryModel::select('store_code', 'store_sub_unit_short_name')
                ->distinct()
                ->get();

            // Retrieve cached MGIOS master data or initialize empty array
            $cacheKey = 'mgios_item_masterdata';
            $itemMasterData = Cache::get($cacheKey, []);

            foreach ($storeCollection as $store) {
                $storeCode = $store['store_code'];
                $storeSubUnit = $store['store_sub_unit_short_name'];

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

            DB::commit();
            \Log::info('Initial inventory generation completed successfully.');
        } catch (\Throwable $exception) {
            DB::rollBack();
            \Log::info('Error generating initial inventory: ' . $exception->getMessage());
        }
    }

    private function onArrayDiffItems($storeSubUnit, $existingItems)
    {
        $addedItems = ["IND7104", "IND7073", "IND3001", "IND7074", "IND7075", "IND4035", "IND1004", "IND1005", "IND1006", "IND7069", "IND1007", "IND7094", "IND1008", "IND1009", "IND1010", "IND1011", "IND1012", "IND1013", "IND3002", "IND1014", "IND1015", "IND5072", "IND1016", "IND1017", "IND1018", "IND1019", "IND1020", "IND1021", "IND1022", "IND1024", "IND1025", "IND1023", "IND1026", "IND1027", "IND1028", "IND1029", "IND1030", "IND1031", "IND1032", "IND1035", "IND1036", "IND1033", "IND1034", "IND1147", "IND1037", "IND1038", "IND1039", "IND1040", "IND1041", "IND1042", "IND1043", "IND1044", "IND1045", "IND1046", "IND1047", "IND5001", "IND4001", "IND7089", "IND3003", "IND5002", "IND1049", "IND1048", "IND7068", "IND1050", "IND1051", "IND7145", "IND7144", "IND7146", "IND7143", "IND1052", "IND1053", "IND2036", "IND7119", "IND7176", "IND7175", "IND7177", "IND7174", "IND3004", "IND3005", "IND1054", "IND2037", "IND7114", "IND7113", "IND7115", "IND7111", "IND7112", "IND7136", "IND7362", "IND2038", "IND7108", "IND5073", "IND7105", "IND7101", "IND1055", "IND5003", "IND7107", "IND7157", "IND7067", "IND5006", "IND7103", "IND7164", "IND7165", "IND2040", "IND2039", "IND5007", "IND5008", "IND4002", "IND5009", "IND5010", "IND4036", "IND5011", "IND4037", "IND1056", "IND1057", "IND1058", "IND1059", "IND1060", "IND5013", "IND5012", "IND5014", "IND1061", "IND4003", "IND4038", "IND4004", "IND4039", "IND4040", "IND4041", "IND4006", "IND4042", "IND4043", "IND4044", "IND4007", "IND5015", "IND7078", "IND7077", "IND7079", "IND5019", "IND2041", "IND1062", "IND5020", "IND1063", "IND7095", "IND1065", "IND1066", "IND1067", "IND4008", "IND4045", "IND5021", "IND1068", "IND5022", "IND5023", "IND5024", "IND5025", "IND7141", "IND7142", "IND7147", "IND4046", "IND7293", "IND2042", "IND1069", "IND1070", "IND1071", "IND7166", "IND5026", "IND1072", "IND7178", "IND7148", "IND5027", "IND7064", "IND5074", "IND5028", "IND5029", "IND5030", "IND5031", "IND5033", "IND7096", "IND5034", "IND5035", "IND5075", "IND5076", "IND7090", "IND2043", "IND5036", "IND5041", "IND5004", "IND5005", "IND5037", "IND5042", "IND5077", "IND5078", "IND5038", "IND5043", "IND7118", "IND5039", "IND5040", "IND5044", "IND7109", "IND1073", "IND1074", "IND1075", "IND4009", "IND4010", "IND4011", "IND1076", "IND1077", "IND7167", "IND2044", "IND2045", "IND2046", "IND2047", "IND1078", "IND1079", "IND1080", "IND1081", "IND1082", "IND1083", "IND1084", "IND7161", "IND7160", "IND1085", "IND1086", "IND1087", "IND1088", "IND7140", "IND1089", "IND1090", "IND1091", "IND1092", "IND7070", "IND3007", "IND1093", "IND1094", "IND1095", "IND1096", "IND1097", "IND1098", "IND1099", "IND1100", "IND1101", "IND1102", "IND1103", "IND3008", "IND1104", "IND1105", "IND1106", "IND1107", "IND1108", "IND1109", "IND5045", "IND5046", "IND4012", "IND4013", "IND2061", "IND4014", "IND4015", "IND7098", "IND7099", "IND7100", "IND7062", "IND5047", "IND2048", "IND1111", "IND4016", "IND2049", "IND4017", "IND7172", "IND3009", "IND4018", "IND4019", "IND5048", "IND5049", "IND5050", "IND5051", "IND2050", "IND7135", "IND5052", "IND7110", "IND7106", "IND5053", "IND5054", "IND5055", "IND5056", "IND5057", "IND5058", "IND7097", "IND3010", "IND3031", "IND3011", "IND3012", "IND1112", "IND1113", "IND1114", "IND4021", "IND4022", "IND4023", "IND4024", "IND4025", "IND4026", "IND4027", "IND2062", "IND4030", "IND4031", "IND2051", "IND2063", "IND2064", "IND4020", "IND2052", "IND2053", "IND2054", "IND2055", "IND2056", "IND2057", "IND2065", "IND4028", "IND4029", "IND4032", "IND2058", "IND2059", "IND7156", "IND4033", "IND4034", "IND1115", "IND1116", "IND3013", "IND7065", "IND5059", "IND5060", "IND5079", "IND5061", "IND5062", "IND5063", "IND5064", "IND5065", "IND1117", "IND3014", "IND3015", "IND3016", "IND5066", "IND5067", "IND5068", "IND5069", "IND5070", "IND1118", "IND1119", "MF3010", "IND2060", "IND1120", "IND1121", "IND1122", "IND1123", "IND7102", "IND1124", "IND7137", "IND7173", "IND1125", "IND1126", "IND1164", "IND1167", "IND1127", "IND1128", "IND1129", "IND1130", "IND1131", "IND5071", "ADM001", "ADM002", "ADM003", "ADM004", "ADM005", "ADM006", "ADM007", "BAT_AA", "BAT_AAA", "BOARD", "TPS0334", "BPAPER LONG", "BPAPER SHORT", "INK LC3619XL", "B.ENVE LONG", "B.ENVE SHORT", "CALC", "CARBO", "C.VOUCHER .5", "C.VOUCHER .25", "C.C.5", "C.VOUCHER C.25", "CLBOOK BLUE", "CLBOOK RED", "CLB REF. LONG", "CLB REF. SHORT", "CBB", "CBM", "CBS", "C002", "COR. TAPE", "DST", "DST- GREEN", "D. GUN", "D. TAPE", "EP BLK", "EP CYN", "EP MAG", "EP YLW", "E. PENCIL", "E. BOARD", "EXP. ENVE. LONG", "EXP. ENVE. SHORT", "FAST.", "F. LONG", "F. SHORT", "ELMERS", "STICK PASTE", "COPY A3", "H. LBLUE", "H. LGREEN", "H. LPINK", "H. LYELLOW", "ILT", "IMAGING UNIT", "CARD_BLUE", "CARD_GREEN", "CARD_PINK", "CARD_WHITE", "CARD_YELLOW", "INK_810", "INK_811", "INK_88", "INK_98", "INK_005", "INK_678B", "INK_678C", "INK_680B", "INK_680C", "INK_682B", "INK_682C", "INK_704B", "INK_704C", "IWATA", "IWATA_ EQ", "FILM_65X95", "FILM_80X110", "SHEET_LONG", "SHEET_SHORT", "MZN", "MZN_RACK", "MNTRAINEE", "MAG_N.PLATES", "MNGR. APRON", "M_PAPER", "TAPE_BIG", "TAPE_SMALL", "MG_PIN", "MDETECTOR", "NBO", "NBV", "TAPE_BROWN", "TAPE_CLEAR", "PCLIP_BIG", "PCLIP_SMALL", "SPINDLE", "PARCH.PAPER", "PAY_ENVE", "MAYA_THERMAL", "PENCIL", "PERMA_BLACK", "PERMA_INKBLK", "PERMA_BLUE", "PERMA_INK BLUE", "PERMA_RED", "PERMA_INK RED", "P.PAPER", "P.COVER", "PLAST_LONG", "PLAST_SHORT", "PLAST_FOL. LONG", "PLAST_FOL. SHRT", "PT_GREEN", "PT_PINK", "PT_WHITE", "PUNCH", "PUSH_PIN", "RAGS", "R_BOOK 500", "RING _BLACK", "RING _BLUE", "R_BAND", "RLR", "SCIS", "S_TAPE", "VAULT", "SHARP_SMALL", "NWPL", "NWPS", "STAMP_BLACK", "STAMP_BLUE", "STAMP_CLEAR", "STAMP_INKBLK", "STAMP_INKBL", "STAPL_FREE", "STAPL_REMVR", "STAPL_WIRE", "STICK_MATTE", "PST_MED", "PST_SMALL", "TAPE_DISP.", "THERM_STAND", "TIME _RACK", "TIME_CARD", "TONER_303", "TONER_325", "TONER_337", "TONER_LASERJET", "TONER_D303", "TONER_T06", "FOH-F", "FOH-M", "FOH004", "FOH003", "FOH002", "FOH005", "UNLI _LONG", "UNLI _SHRT", "WHITEBRD_BLK", "WHITEBRD_INKBLK", "WHITEBRD_BL", "WHITEBRD_INKBL", "WHITEBRD_RED", "WHITE_ENVE", "WHITEBRD_INKRED", "YELLO_PAPER", "IWATA_BULB", "T002", "T004", "T001", "T003", "CF410A_TONER", "CF410A_CTONER", "CF410A_MTONER", "CF410A_YTONER", "CE255A", "T005", "BAT_9V", "CRA_CAF", "RING_REFILLONG", "FOH006", "FOH001", "WBOOK", "BAO", "WLAN", "AWAVICASY RAH21", "KZC B5B1", "KZC B5B", "KZC B5B3", "KZC B5B2", "KZC B4B", "PRMRPH2021", "SPARKLING", "WHITE", "RED", "SWEET", "T593", "OFFICE101", "TRANS1001", "TSF: 11X9.5", "CLOG", "PRINTER", "RIBBON", "CAN_745B", "CAN_746C", "PT_BLUE", "T564", "CSH_CAF", "DTMT", "MOB_PED", "BRO1", "BRO2", "FOLD_TAB", "DIG_CLOCK", "PVC ID", "650634", "RCP 7580-88", "H20_DISP", "FIRE EX BN DRY (CHEM)", "FIRE EX BN (HCFC)", "FIRE EX BN (AFFF)", "FIRE EX BN (WET CHEM)", "FIRE BLANKET", "INK_001B", "INK_001C", "INK_001M", "INK_001Y", "IND6001", "IND6002", "IND6003", "IND6004", "IND7001", "IND7117", "IND7002", "IND6005", "IND6006", "IND7003", "IND6007", "IND6008", "IND6010", "IND6011", "IND7358", "IND6012", "IND6013", "IND6014", "IND6015", "IND6016", "IND6017", "IND6018", "IND6019", "IND6020", "IND6021", "IND6022", "IND6023", "IND6024", "IND7092", "CAPPUCCINO/LATE CUPS", "IND6025", "IND7154", "IND6026", "IND6027", "IND6028", "IND6029", "IND6030", "IND6031", "IND6032", "IND6033", "IND6034", "IND6035", "IND6036", "IND7004", "IND6037", "IND6038", "IND6039", "IND6040", "IND6041", "IND6042", "IND6043", "IND7055", "IND6044", "IND7081", "IND7120", "IND7006", "IND7091", "IND7203", "IND6045", "IND6046", "IND6047", "IND6048", "IND6049", "IND7007", "IND7008", "IND6050", "IND6051", "IND7009", "IND7139", "IND7057", "IND7010", "IND7011", "IND7129", "IND7168", "IND6052", "IND6053", "IND6054", "IND6055", "IND6056", "IND6057", "IND6058", "IND6059", "IND6060", "IND6061", "IND6062", "IND7013", "IND7126", "IND6063", "IND6064", "IND6065", "IND6066", "IND6067", "IND6068", "IND6069", "IND6070", "IND6071", "IND7133", "IND6072", "IND6073", "IND6074", "IND7014", "IND6075", "IND6076", "IND6077", "IND6078", "IND6079", "IND7124", "IND7015", "IND7153", "IND6080", "IND7159", "IND6081", "IND6082", "IND6083", "IND6084", "IND6085", "IND6086", "IND6087", "IND7366", "IND7125", "IND7058", "IND7059", "IND6088", "IND7130", "IND7131", "IND7162", "IND7085", "IND7083", "IND7084", "IND6089", "IND7151", "IND7150", "IND6090", "IND6091", "IND7060", "IND7016", "IND7017", "IND7018", "IND6093", "IND6094", "IND6095", "IND6096", "IND6097", "IND6098", "IND6099", "IND6100", "IND6101", "IND7019", "IND7356", "IND6103", "IND7061", "IND7020", "IND6104", "IND7021", "IND7022", "IND7023", "IND7024", "IND7025", "IND7026", "IND6105", "IND6106", "IND6107", "IND6108", "IND6109", "IND7376", "IND7027", "IND6111", "IND7123", "IND6160", "IND7029", "IND6161", "IND7127", "IND7128", "IND7170", "IND6112", "IND6113", "IND6114", "IND6115", "IND7171", "IND7063", "IND6116", "IND6117", "IND6118", "IND7030", "IND7080", "IND6120", "IND7072", "IND6122", "IND6123", "IND6124", "IND6125", "IND7031", "IND7149", "IND7032", "IND7521", "IND7121", "IND7034", "IND7035", "IND7036", "IND6127", "IND6128", "IND7037", "IND7038", "IND7039", "IND6129", "IND7152", "IND7169", "IND7158", "IND6130", "IND6131", "IND6132", "IND6133", "IND7560", "IND7082", "IND6134", "IND6135", "IND6136", "IND7040", "IND7041", "IND7042", "IND7043", "IND6139", "IND7591", "IND6140", "IND6141", "IND6142", "IND6143", "IND6144", "IND6145", "IND7044", "IND7045", "IND7046", "IND7087", "UNIFORM APRON", "UNIFORM APRON21 MALE", "VANILLA OIL THERAPY", "IND7047", "IND7048", "IND7049", "IND7050", "IND7051", "IND7052", "IND7053", "IND7054", "IND7155", "IND6146", "IND6147", "IND6148", "IND7134", "IND6149", "IND6150", "IND6151", "IND6152", "IND6153", "FA-044", "ELEC-178", "ELEC-179", "ELEC-464", "FA-043", "FA-030", "FA-018", "F-1025", "F-1029-R", "F-1027", "GL-10001", "GL-10013", "GL-10006", "F-1015", "BT-022", "BT-048", "EQUIP-084", "FA-012", "FA-019", "FA-040", "EQUIP-345", "EQUIP-501", "TOOLS-561", "EQUIP-412"];

        $cafeItems = [];
        if ($storeSubUnit == 'FOH') {
            $cafeItems[] = ['EQ 6', 'CR 12', 'MM 6', 'BR 8', 'BR 4', 'FFG 8', 'FFG 4', 'LMS 8', 'LMS 4', 'AP WH', 'AP MN', 'TAS WH', 'TAS MN', 'VT WH', 'VT MN', 'CK WH', 'CC WH', 'CC MN', 'CH WH', 'CH MN', 'CM WH', 'CM MN', 'LI WH', 'MB WH', 'SSCV WH', 'SSCV MN', 'SW WH', 'TR WH', 'TR MN', 'BA WH', 'CTI WH', 'CTI MN', 'TROSE1', 'DCC1', 'TLC WH', 'AFFOG WH', 'FC LF', 'PU LF', 'BD LF', 'LGB J', 'LGQ J', 'IND7592', 'IND7594', 'IND7142', 'IND7141', 'IND7147', 'IND7144', 'IND7143', 'IND7145', 'IND7146', 'IND2039', 'IND1069', 'RMDD073', 'RMDD070', 'RMDD071', 'RMDD072', 'RMDD091', 'RMDD121', 'RMDD105', 'RMDD106', 'RMDD107', 'RMDD104', 'RMDD108', 'FG0055', 'FG0056', 'FG0057', 'FG0084', 'FG0053', 'RMDD022', 'RMDD021', 'RMDD062', 'FG0080', 'FG0061', 'FG0070', 'FG0074', 'FG0075', 'FG0085'];
        } else if ($storeSubUnit == 'BOH') {
            $cafeItems[] = ['FG0005', 'FG0015', 'FG0020', 'FG0008', 'FG0007', 'FG0013', 'FG0017', 'FG0001', 'FG0018', 'FG0106', 'FG0010', 'FG0011', 'FG0006', 'FG0016', 'FG0002', 'FG0014', 'FG0019', 'FG0019', 'FG0050', 'FG0051', 'FG0003', 'FG0045', 'FG0046', 'FG0039', 'FG0077', 'FG0083', 'FG0082', 'FG0103', 'FG0104', 'FG0107', 'FG0105', 'FG0088', 'FG0040', 'FG0052', 'FG0090', 'FG0048', 'FG0102', 'RMDD009', 'FG0076', 'FG0086', 'RMDD030', 'RMDD042', 'RMDD055', 'RMDD031', 'RMDD018', 'TW025', 'FG0034', 'FG0012', 'FG0035', 'FG0037', 'FG0033', 'FG0036', 'FG0038', 'SF-BGU01', 'FG0032', 'FG0024', 'FG0022', 'FG0027', 'FG0030', 'FG0026', 'FG0099', 'FG0028', 'FG0021', 'FG0029', 'FG0025', 'FG0097', 'FG0023', 'FG0089', 'FG0091', 'FG0071', 'FG0062', 'FG0072', 'FG0064', 'FG0065', 'FG0063', 'TW003', 'TW029', 'TW001', 'RMDD097', 'FG0004', 'RMDD067', 'IND7076', 'IND7396', 'IND1055', 'IND1068', 'IND1111', 'IND1007', 'IND1118', 'IND1119', 'IND1014', 'RMDD063', 'RMDD061', 'RMDD003', 'FG0093', 'RMDD015', 'RMDD038', 'RMDD080', 'RMDD102', 'RMDD084', 'RMDD092', 'RMDD115', 'RMDD113', 'RMDD039', 'RMDD077', 'RMDD078', 'FG0067', 'FG0068', 'FG0069', 'FG0058', 'FG0059', 'FG0054', 'FG0066', 'FG0081', 'FG0092', 'RMDD004', 'FG0091', 'FG0100', 'RMDD069', 'RMDD049', 'RMDD050', 'RMDD048', 'RMDD051', 'RMDD045', 'RMDD100', 'RMDD094', 'RMDD026', 'RMDD001', 'RMDD008', 'RMDD044', 'RMDD043', 'RMDD058', 'FG0079', 'RMDD090', 'RMDD002', 'RMDD052', 'RMDD047', 'RMDD007', 'RMDD011', 'RMDD027', 'RMDD034', 'RMDD035', 'RMDD036', 'RMDD037', 'RMDD057', 'RMDD065', 'RMDD101', 'FG0047', 'TW005', 'TW009', 'TW006', 'TW007', 'TW010', 'TW016', 'TW018', 'RMDD010', 'RMDD114', 'RMDD023', 'TW019', 'TW017', 'TW020', 'TW021', 'TW022', 'TW026', 'TW027', 'RMDD005', 'RMDD059', 'RMDD060', 'RMDD029', 'RMDD054', 'RMDD089', 'FG0096', 'RMDD111', 'RMDD112', 'RMDD028', 'RMDD014', 'RMDD025', 'RMDD019', 'RMDD040', 'RMDD053', 'RMDD074', 'RMDD068'];
        }
        if (count($cafeItems) > 0) {
            $addedItems = array_merge($addedItems, $cafeItems[0]);
        }
        return array_values(array_diff($addedItems, $existingItems));
    }
}
