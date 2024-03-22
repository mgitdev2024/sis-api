<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Items\ItemMasterdata;

class ItemMasterdataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdById = 1;
        $itemMasterdata = [
            [
                'description' => 'Cheeseroll Box of 6',
                'item_code' => 'CR6 BX',
                'item_classification_id' => '1',
                'item_variant_type_id' => '7',
                'conversion_id' => '1',
                'primary_item_packing_size' => 1,
                'secondary_item_packing_size' => 6,
                'plant_id' => '2',
                'shelf_life' => '6',
            ],
            [
                'description' => 'Chocolate Cake',
                'item_code' => 'CHOC BX',
                'item_classification_id' => '2',
                'item_variant_type_id' => '1',
                'conversion_id' => '1',
                'primary_item_packing_size' => 1,
                'secondary_item_packing_size' => 1,
                'plant_id' => '2',
                'shelf_life' => '7',
            ],
            [
                'description' => 'Chocolate truffle Cake',
                'item_code' => 'TRUF BX',
                'item_classification_id' => '2',
                'item_variant_type_id' => '1',
                'conversion_id' => '1',
                'primary_item_packing_size' => 1,
                'secondary_item_packing_size' => 1,
                'plant_id' => '2',
                'shelf_life' => '7',
            ],
            [
                'description' => 'Mamon Box of 6',
                'item_code' => 'MM6',
                'item_classification_id' => '1',
                'item_variant_type_id' => '7',
                'conversion_id' => '1',
                'primary_item_packing_size' => 1,
                'secondary_item_packing_size' => 6,
                'plant_id' => '2',
                'shelf_life' => '4',
            ],
            [
                'description' => 'Banana Bread',
                'item_code' => 'BD',
                'item_classification_id' => '4',
                'item_variant_type_id' => '9',
                'conversion_id' => '1',
                'primary_item_packing_size' => 1,
                'secondary_item_packing_size' => 1,
                'plant_id' => '2',
                'shelf_life' => '3',
            ],
        ];

        foreach ($itemMasterdata as $value) {
            ItemMasterdata::create([
                'created_by_id' => $createdById,
                'description' => $value['description'],
                'item_code' => $value['item_code'],
                'item_classification_id' => $value['item_classification_id'],
                'item_variant_type_id' => $value['item_variant_type_id'],
                'plant_id' => $value['plant_id'],
                'shelf_life' => $value['shelf_life'],
                'conversion_id' => $value['conversion_id'],
                'primary_item_packing_size' => $value['primary_item_packing_size'],
                'secondary_item_packing_size' => $value['secondary_item_packing_size'],
            ]);
        }
    }
}
