<?php

namespace Database\Seeders\WmsSeeder;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\WMS\Settings\ItemMasterData\ItemCategoryModel;

class ItemCategorySeeder extends Seeder
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
                'code' => 'CAT-BRE',
                'name' => 'Breads',
            ],
            [
                'code' => 'CAT-CAK',
                'name' => 'Cakes',
            ],
            [
                'code' => 'CAT-PAS',
                'name' => 'Pastries',
            ],
            [
                'code' => 'CAT-LOA',
                'name' => 'Loaves',
            ],
            [
                'code' => 'CAT-OTH',
                'name' => 'Others',
            ],
        ];
        $createdById = 1;

        foreach ($classifications as $value) {
            ItemCategoryModel::create([

                'code' => $value['code'],
                'created_by_id' => $createdById,
                'name' => $value['name']
            ]);
        }
    }
}
