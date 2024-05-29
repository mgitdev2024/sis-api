<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\ItemMasterData\ItemClassificationModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $classifications = [
            [
                'code' => 'CLS-FG',
                'short_name' => 'FG',
                'long_name' => 'Finish Goods',
            ],
            [
                'code' => 'CLS-RM',
                'short_name' => 'FG',
                'long_name' => 'Raw Materials',
            ]
        ];
        $createdById = 1;

        foreach ($classifications as $value) {
            ItemClassificationModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'short_name' => $value['short_name']
            ]);
        }
    }
}
