<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Items\ItemVariantType;

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
            ItemVariantType::create([
                'created_by_id' => $createdById,
                'name' => $name,
            ]);
        }
    }
}
