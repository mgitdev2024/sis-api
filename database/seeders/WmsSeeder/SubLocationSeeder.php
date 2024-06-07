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
        $subLocations = [
            [
                "code" => "RACK-1",
                "number" => "1",
                "has_layer" => 1,
                "is_permanent" => 1,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2}}',
                "facility_id" => 2,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1

            ],
            [
                "code" => "RACK-2",
                "number" => "2",
                "has_layer" => 1,
                "is_permanent" => 0,
                "layers" => '{"1":{"min":1,"max":20,"layer_no":1},"2":{"min":1,"max":20,"layer_no":2}}',
                "facility_id" => 2,
                "warehouse_id" => 1,
                "zone_id" => 1,
                "sub_location_type_id" => 1

            ],
        ];
        $createdById = 1;

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
