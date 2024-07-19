<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\ItemMasterData\ItemStockTypeModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StockTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $stockType = [
            [
                'code' => 'MTO',
                'short_name' => 'MTO',
                'long_name' => 'Made to Order',
            ],
            [
                'code' => 'MTS',
                'short_name' => 'MTS',
                'long_name' => 'Made to Stock',
            ],
        ];
        $createdById = 0000;

        foreach ($stockType as $value) {
            ItemStockTypeModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name'],
            ]);
        }
    }
}
