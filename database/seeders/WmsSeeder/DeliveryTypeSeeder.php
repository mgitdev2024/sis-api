<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\ItemMasterData\ItemDeliveryTypeModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeliveryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdById = 1;
        $classifications = [
            [
                'code' => '1D',
                'name' => 'First Delivery'
            ],
            [
                'code' => '2D',
                'name' => 'Second Delivery'
            ],
            [
                'code' => '3D',
                'name' => 'Third Delivery'
            ],
        ];

        foreach ($classifications as $value) {
            ItemDeliveryTypeModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'name' => $value['name'],
            ]);
        }
    }
}
