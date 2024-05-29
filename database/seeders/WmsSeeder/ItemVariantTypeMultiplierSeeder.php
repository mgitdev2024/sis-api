<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\ItemMasterData\ItemVariantTypeMultiplierModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemVariantTypeMultiplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $variantType = [
            ['item_variant_type_id' => 1, 'multiplier' => 2],
            ['item_variant_type_id' => 2, 'multiplier' => 2],
            ['item_variant_type_id' => 3, 'multiplier' => 2],
            ['item_variant_type_id' => 4, 'multiplier' => 2],
            ['item_variant_type_id' => 5, 'multiplier' => 2],
            ['item_variant_type_id' => 6, 'multiplier' => 2],
            ['item_variant_type_id' => 7, 'multiplier' => 2],
            ['item_variant_type_id' => 8, 'multiplier' => 2],
            ['item_variant_type_id' => 9, 'multiplier' => 2],
            ['item_variant_type_id' => 10, 'multiplier' => 2],
            ['item_variant_type_id' => 11, 'multiplier' => 2],
        ];

        $createdById = 1;

        foreach ($variantType as $value) {
            ItemVariantTypeMultiplierModel::create([
                'created_by_id' => $createdById,
                'item_variant_type_id' => $value['item_variant_type_id'],
                'multiplier' => $value['multiplier']
            ]);
        }
    }
}
