<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationTypeModel;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubLocationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $subLocationType = [
            [
                "code" => "SLT-RCK",
                "short_name" => "Rack Chill",
                "long_name" => "Rack Chill",
            ],
            [
                "code" => "SLT-CRTS",
                "short_name" => "Crate Chill",
                "long_name" => "Crate Chill",

            ]
        ];
        $createdById = 0000;

        foreach ($subLocationType as $value) {
            SubLocationTypeModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name'],
            ]);
        }
    }

    public function onGetFacilityId($value)
    {
        $facilityCode = $value;

        $facility = FacilityPlantModel::where('code', $facilityCode)->first();

        return $facility ? $facility->id : null;
    }

    public function onGetWarehouseId($value)
    {
        $warehouseCode = $value;

        $warehouse = WarehouseModel::where('code', $warehouseCode)->first();

        return $warehouse ? $warehouse->id : null;
    }

    public function onGetZoneId($value)
    {
        $zoneCode = $value;

        $zone = ZoneModel::where('code', $zoneCode)->first();

        return $zone ? $zone->id : null;
    }
}
