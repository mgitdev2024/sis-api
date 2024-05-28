<?php

namespace Database\Seeders\MosSeeder;

use App\Models\Settings\Facility\PlantModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plantData = [
            [
                'short_name' => 'Commi',
                'long_name' => 'Commissary',
                'plant_code' => '01',
                'description' => 'Facility that handles proccessed goods through cooking',
                'created_by_id' => '1',
            ],
            [
                'short_name' => 'Bakery',
                'long_name' => 'Bakery',
                'plant_code' => '02',
                'description' => 'Facility that handles baked goods',
                'created_by_id' => '1',
            ],
            [
                'short_name' => 'Central',
                'long_name' => 'Central Warehouse',
                'plant_code' => '03',
                'description' => 'Facility that handles baked goods',
                'created_by_id' => '1',
            ],
            [
                'short_name' => 'Engr',
                'long_name' => 'Engineering',
                'plant_code' => '05',
                'description' => 'Facility that handles industrial materials',
                'created_by_id' => '1',
            ],
            [
                'short_name' => 'HO',
                'long_name' => 'Head Office',
                'plant_code' => '06',
                'description' => 'Facility that handles head office materials',
                'created_by_id' => '1',
            ],
        ];
        $createdById = 1;

        foreach ($plantData as $value) {
            PlantModel::create([
                'created_by_id' => $createdById,
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name'],
                'plant_code' => $value['plant_code'],
                'description' => $value['description'],
            ]);
        }
    }
}
