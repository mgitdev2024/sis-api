<?php

namespace Database\Seeders\WmsSeeder;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WMS\Settings\ItemMasterData\ItemVariantTypeModel;

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
            [
                'code' => 'VRNT-WHO',
                'name' => 'Whole',
                'short_name' => 'Whole'
            ],
            [
                'code' => 'VRNT-MIN',
                'name' => 'Mini',
                'short_name' => 'Mini'
            ],
            [
                'code' => 'VRNT-SLI',
                'name' => 'Slice',
                'short_name' => 'Slice'
            ],
            [
                'code' => 'VRNT-BO8',
                'name' => 'Box of 8s',
                'short_name' => 'BO8'
            ],
            [
                'code' => 'VRNT-BO4',
                'name' => 'Box of 4s',
                'short_name' => 'BO4'
            ],
            [
                'code' => 'VRNT-BO1',
                'name' => 'Box of 12s',
                'short_name' => 'BO12'
            ],
            [
                'code' => 'VRNT-BO6',
                'name' => 'Box of 6s',
                'short_name' => 'BO6'
            ],
            [
                'code' => 'VRNT-PIE',
                'name' => 'Piece',
                'short_name' => 'Piece'
            ],
            [
                'code' => 'VRNT-JAR',
                'name' => 'Jars',
                'short_name' => 'Jars'
            ],
            [
                'code' => 'VRNT-LOA',
                'name' => 'Loaf',
                'short_name' => 'Loaf'
            ],
            [
                'code' => 'VRNT-PAC',
                'name' => 'Packs',
                'short_name' => 'Packs'
            ],
        ];



        $createdById = 1;

        foreach ($variantType as $value) {
            ItemVariantTypeModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'name' => $value['name'],
                'short_name' => $value['short_name']
            ]);
        }
    }
}
