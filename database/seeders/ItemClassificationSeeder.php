<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings\Items\ItemClassificationModel;

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
                'name' => 'Breads',
            ],
            [
                'name' => 'Cakes',
            ],
            [
                'name' => 'Pastries',
            ],
            [
                'name' => 'Loaves',
            ],
            [
                'name' => 'Others',
            ],
        ];
        $createdById = 1;

        foreach ($classifications as $value) {
            ItemClassificationModel::create([
                'created_by_id' => $createdById,
                'name' => $value['name']
            ]);
        }
    }
}
