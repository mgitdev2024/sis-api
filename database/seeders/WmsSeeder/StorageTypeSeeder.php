<?php

namespace Database\Seeders\WmsSeeder;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;

class StorageTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $storageType = [
            [
                'code' => 'ST-AMB',
                'short_name' => 'Ambient',
                'long_name' => 'Ambient Storage',
            ],
            [
                'code' => 'ST-CHI',
                'short_name' => 'Chilled',
                'long_name' => 'Chilled Storage',
            ],
            [
                'code' => 'ST-FRO',
                'short_name' => 'Frozen',
                'long_name' => 'Frozen Storage',
            ],
        ];
        $createdById = 1;

        foreach ($storageType as $value) {
            StorageTypeModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name']
            ]);
        }
    }
}
