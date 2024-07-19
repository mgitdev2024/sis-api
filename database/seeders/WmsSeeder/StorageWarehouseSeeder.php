<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;

class StorageWarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $storageWarehouse = [
            [
                "code" => "WARE-FG",
                "short_name" => "FG",
                "long_name" => "Finish Goods",
                "description" => "",
                "facility_code" => "02"
            ],
            [
                "code" => "WARE-RM",
                "short_name" => "RM",
                "long_name" => "Raw Materials",
                "description" => "",
                "facility_code" => "01"
            ]
        ];


        $createdById = 0000;

        foreach ($storageWarehouse as $value) {
            WarehouseModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name'],
                'description' => $value['description'],
                'facility_id' => $this->onGetFacilityId($value['facility_code'])
            ]);
        }
    }

    public function onGetFacilityId($value)
    {
        $facilityCode = $value;

        $facility = FacilityPlantModel::where('code', $facilityCode)->first();

        return $facility ? $facility->id : null;
    }
}
