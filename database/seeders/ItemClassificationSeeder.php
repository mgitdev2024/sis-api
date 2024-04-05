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
                'sticker_multiplier' => 2,
            ],
            [
                'name' => 'Cakes',
                'sticker_multiplier' => 2,
            ],
            [
                'name' => 'Pastries',
                'sticker_multiplier' => 1,
            ],
            [
                'name' => 'Loaves',
                'sticker_multiplier' => 1,
            ],
            [
                'name' => 'Others',
                'sticker_multiplier' => 1,
            ],
        ];
        $createdById = 1;

        foreach ($classifications as $value) {
            ItemClassificationModel::create([
                'created_by_id' => $createdById,
                'name' => $value['name'],
                'sticker_multiplier' => $value['sticker_multiplier'],
            ]);
        }
    }
}
