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
                'name' => 'Cheeseroll Box of 6',
                'item_code' => 'CR6 BX',
                'item_classification_id' => '1',
            ],
            [
                'name' => 'Chocolate Cake',
                'item_code' => 'CHOC BX',
                'item_classification_id' => '2',
            ],
            [
                'name' => 'Chocolate truffle Cake',
                'item_code' => 'TRUF BX',
                'item_classification_id' => '2',
            ],
            [
                'name' => 'Mamon Box of 6',
                'item_code' => 'MM6',
                'item_classification_id' => '1',
            ],
            [
                'name' => 'Banana Bread',
                'item_code' => 'BD',
                'item_classification_id' => '4',
            ],
        ];

        foreach ($itemMasterdata as $value) {
            ItemMasterdata::create([
                'created_by_id' => $createdById,
                'name' => $value['name'],
                'item_code' => $value['item_code'],
                'item_classification_id' => $value['item_classification_id'],
            ]);
        }
    }
}
