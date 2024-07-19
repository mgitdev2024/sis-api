<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\ItemMasterData\ItemMovementModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $itemMovement = [
            [
                'code' => 'A',
                'short_name' => 'Fast Moving',
                'long_name' => 'Fast Moving',
            ],
            [
                'code' => 'B',
                'short_name' => 'Slow Moving',
                'long_name' => 'Slow Moving',
            ],
            [
                'code' => 'C',
                'short_name' => 'Non Moving',
                'long_name' => 'Non Moving',
            ]
        ];
        $createdById = 0000;

        foreach ($itemMovement as $value) {
            ItemMovementModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name'],
            ]);
        }
    }
}
