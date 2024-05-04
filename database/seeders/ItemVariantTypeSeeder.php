<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings\Items\ItemVariantTypeModel;

class ItemVariantTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $variantType = [
            ['name' => 'Whole', 'sticker_multiplier' => 2],
            ['name' => 'Mini', 'sticker_multiplier' => 2],
            ['name' => 'Slice', 'sticker_multiplier' => 2],
            ['name' => 'Box of 8s', 'sticker_multiplier' => 2],
            ['name' => 'Box of 4s', 'sticker_multiplier' => 2],
            ['name' => 'Box of 12s', 'sticker_multiplier' => 2],
            ['name' => 'Box of 6s', 'sticker_multiplier' => 2],
            ['name' => 'Piece', 'sticker_multiplier' => 2],
            ['name' => 'Jars', 'sticker_multiplier' => 2],
            ['name' => 'Loaf', 'sticker_multiplier' => 2],
            ['name' => 'Packs', 'sticker_multiplier' => 2],
        ];

        $createdById = 1;

        foreach ($variantType as $value) {
            ItemVariantTypeModel::create([
                'created_by_id' => $createdById,
                'name' => $value['name'],
                'sticker_multiplier' => $value['sticker_multiplier'],
            ]);
        }
    }
}
