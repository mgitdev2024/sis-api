<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings\Items\ItemMasterdataModel;

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
                'description' => 'Cheeseroll Box of 12',
                'item_code' => 'CR12',
                'item_classification_id' => '1',
                'item_variant_type_id' => '7',
                'uom_id' => '3',
                'primary_item_packing_size' => 12,
                'primary_conversion_id' => 1,
                'secondary_item_packing_size' => 6,
                'secondary_conversion_id' => 1,
                'plant_id' => '2',
                'chilled_shelf_life' => '3',
                'frozen_shelf_life' => '3',
            ],
            [
                'description' => 'Chocolate Cake',
                'item_code' => 'CHOC BX',
                'item_classification_id' => '2',
                'item_variant_type_id' => '1',
                'uom_id' => '3',
                'primary_item_packing_size' => null,
                'primary_conversion_id' => null,
                'secondary_item_packing_size' => null,
                'secondary_conversion_id' => null,
                'plant_id' => '2',
                'chilled_shelf_life' => '3',
                'frozen_shelf_life' => '3',
            ],
            [
                'description' => 'Chocolate truffle Cake',
                'item_code' => 'TRUF BX',
                'item_classification_id' => '2',
                'item_variant_type_id' => '1',
                'uom_id' => '1',
                'primary_item_packing_size' => 1,
                'primary_conversion_id' => 1,
                'secondary_item_packing_size' => 6,
                'secondary_conversion_id' => 1,
                'plant_id' => '2',
                'chilled_shelf_life' => '3',
                'frozen_shelf_life' => '3',
            ],
            [
                'description' => 'Mamon Box of 6',
                'item_code' => 'MM6',
                'item_classification_id' => '1',
                'item_variant_type_id' => '7',
                'uom_id' => '3',
                'primary_item_packing_size' => 6,
                'primary_conversion_id' => 1,
                'secondary_item_packing_size' => 6,
                'secondary_conversion_id' => 1,
                'plant_id' => '2',
                'chilled_shelf_life' => '3',
                'frozen_shelf_life' => '3',
            ],
            [
                'description' => 'Banana Bread',
                'item_code' => 'BD',
                'item_classification_id' => '4',
                'item_variant_type_id' => '9',
                'uom_id' => '1',
                'primary_item_packing_size' => 1,
                'primary_conversion_id' => 1,
                'secondary_item_packing_size' => 6,
                'secondary_conversion_id' => 1,
                'plant_id' => '2',
                'chilled_shelf_life' => '3',
                'frozen_shelf_life' => '3',
            ],
            [
                'description' => 'Chocolate Cake Slice',
                'item_code' => 'CHOC BX SLC',
                'item_classification_id' => '2',
                'item_variant_type_id' => '3',
                'parent_item_id' => '2',
                'uom_id' => '1',
                'primary_item_packing_size' => null,
                'primary_conversion_id' => null,
                'secondary_item_packing_size' => null,
                'secondary_conversion_id' => null,
                'plant_id' => '2',
                'chilled_shelf_life' => '3',
                'frozen_shelf_life' => '3',
            ],
        ];

        foreach ($itemMasterdata as $value) {
            ItemMasterdataModel::create([
                'created_by_id' => $createdById,
                'description' => $value['description'],
                'item_code' => $value['item_code'],
                'item_classification_id' => $value['item_classification_id'],
                'item_variant_type_id' => $value['item_variant_type_id'],
                'plant_id' => $value['plant_id'],
                'parent_item_id' => $value['parent_item_id'] ?? null,
                'chilled_shelf_life' => $value['chilled_shelf_life'],
                'frozen_shelf_life' => $value['frozen_shelf_life'],
                'uom_id' => $value['uom_id'],
                'primary_item_packing_size' => $value['primary_item_packing_size'],
                'secondary_item_packing_size' => $value['secondary_item_packing_size'],
                'primary_conversion_id' => $value['primary_conversion_id'],
                'secondary_conversion_id' => $value['secondary_conversion_id'],
            ]);
        }
    }
}
