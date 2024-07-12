<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings\Measurements\UomModel;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdById = 1;
        $zone = [
            [
                "code" => "Z01",
                "short_name" => "Zone-01",
                "long_name" => "FG Ambient Zone-01",
                "description" => "",
                "facility_code" => 2,
                "warehouse_code" => "WARE-FG",
                "storage_type_code" => "ST-AMB"
            ],
            [
                "code" => "Z02",
                "short_name" => "Zone-02",
                "long_name" => "FG Chiller Zone-02",
                "description" => "",
                "facility_code" => 2,
                "warehouse_code" => "WARE-FG",
                "storage_type_code" => "ST-CHI"
            ],
            [
                "code" => "Z03",
                "short_name" => "Zone-03",
                "long_name" => "FG Frozen Zone-03",
                "description" => "",
                "facility_code" => 2,
                "warehouse_code" => "WARE-FG",
                "storage_type_code" => "ST-FRO"
            ],
        ];



        foreach ($zone as $value) {
            ZoneModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name'],
                'description' => $value['description'],
                'facility_id' => $this->onGetFacilityId($value['facility_code']),
                'warehouse_id' => $this->onGetWarehouseId($value['warehouse_code']),
                'storage_type_id' => $this->onGetStorageTypeId($value['storage_type_code']),
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

    public function onGetStorageTypeId($value)
    {
        $storageTypeId = $value;

        $storageType = StorageTypeModel::where('code', $storageTypeId)->first();

        return $storageType ? $storageType->id : null;
    }
}
