<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationTypeModel;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        #region Ambient Temporary Storage
        $ambientTemporaryStorage = [];
        for ($i = 1; $i <= 50; $i++) {
            $ambientTemporaryStorage[] = [
                "code" => "RCK" . str_pad($i, 3, '0', STR_PAD_LEFT),
                "number" => "1",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":500,"layer_no":1}}',
                "facility_id" => 1,
                "warehouse_id" => null,
                "zone_id" => null,
                "sub_location_type_id" => 1
            ];
        }
        #endregion

        #region Ambient Permanent Storage
        $ambientPermanentStorage = [
            [
                "code" => "RCK011",
                "number" => "11",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK012",
                "number" => "12",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK013",
                "number" => "13",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK014",
                "number" => "14",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK015",
                "number" => "15",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK016",
                "number" => "16",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK017",
                "number" => "17",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK018",
                "number" => "18",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK019",
                "number" => "19",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK020",
                "number" => "20",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1
            ]
        ];
        #endregion

        #region Chiller Temporary Storage
        $chillerTemporaryStorage = [
            [
                "code" => "RCK021",
                "number" => "21",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK022",
                "number" => "22",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK023",
                "number" => "23",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK024",
                "number" => "24",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK025",
                "number" => "25",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK026",
                "number" => "26",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK027",
                "number" => "27",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK028",
                "number" => "28",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK029",
                "number" => "29",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK030",
                "number" => "30",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ]
        ];
        #endregion

        #region Chiller Permanent Storage
        $chillerPermanentStorage = [
            [
                "code" => "RCK031",
                "number" => "31",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK032",
                "number" => "32",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK033",
                "number" => "33",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK034",
                "number" => "34",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK035",
                "number" => "35",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK036",
                "number" => "36",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK037",
                "number" => "37",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK038",
                "number" => "38",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK039",
                "number" => "39",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK040",
                "number" => "40",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 2,
                "sub_location_type_id" => 1
            ]
        ];
        #endregion

        #region Frozen Temporary Storage
        $frozenTemporaryStorage = [
            [
                "code" => "RCK041",
                "number" => "41",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK042",
                "number" => "42",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK043",
                "number" => "43",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK044",
                "number" => "44",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK045",
                "number" => "45",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK046",
                "number" => "46",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK047",
                "number" => "47",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK048",
                "number" => "48",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK049",
                "number" => "49",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => "RCK050",
                "number" => "50",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ]
        ];
        #endregion

        #region Frozen Permanent Storage
        $frozenPermanentStorage = [
            [
                "code" => 'RCK051',
                "number" => "51",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => 'RCK052',
                "number" => "52",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => 'RCK053',
                "number" => "53",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => 'RCK054',
                "number" => "54",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => 'RCK055',
                "number" => "55",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => 'RCK056',
                "number" => "56",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => 'RCK057',
                "number" => "57",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => 'RCK058',
                "number" => "58",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => 'RCK059',
                "number" => "59",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ],
            [
                "code" => 'RCK060',
                "number" => "60",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2},"3":{"min":1,"max":20,"layer_no":3},"4":{"min":1,"max":20,"layer_no":4}}',
                "facility_id" => 1,
                "warehouse_id" => 1,
                "zone_id" => 3,
                "sub_location_type_id" => 1
            ]
        ];
        #endregion

        $subLocations = array_merge($ambientTemporaryStorage, $ambientPermanentStorage);

        $createdById = 0000;

        foreach ($subLocations as $value) {
            SubLocationModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                "number" => $value['number'],
                "has_layer" => $value['has_layer'],
                "is_permanent" => $value['is_permanent'],
                "layers" => $value['layers'],
                "facility_id" => $value['facility_id'],
                "warehouse_id" => $value['warehouse_id'],
                "zone_id" => $value['zone_id'],
                "sub_location_type_id" => $value['sub_location_type_id']
            ]);
        }
    }
}
