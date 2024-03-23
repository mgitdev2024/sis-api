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
        $variantType = ['Whole', 'Mini', 'Slice', 'Box of 8s', 'Box of 4s', 'Box of 12s', 'Box of 6s', 'Piece', 'Jars', 'Loaf', 'Packs'];
        $createdById = 1;

        foreach ($variantType as $name) {
            ItemVariantTypeModel::create([
                'created_by_id' => $createdById,
                'name' => $name,
            ]);
        }
    }
}
