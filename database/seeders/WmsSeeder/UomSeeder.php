<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\ItemMasterData\ItemUomModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings\Measurements\UomModel;

class UomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdById = 0000;
        $conversionUnits = [
            [
                'code' => 'UOM-PCS',
                'short_name' => 'Pcs',
                'long_name' => 'Piece',
            ],
            [
                'code' => 'UOM-PCK',
                'short_name' => 'Pck',
                'long_name' => 'Pack',
            ],
            [
                'code' => 'UOM-BOX',
                'short_name' => 'Box',
                'long_name' => 'Box',
            ],
            [
                'code' => 'UOM-SET',
                'short_name' => 'Set',
                'long_name' => 'Set',
            ],
            [
                'code' => 'UOM-CLM',
                'short_name' => 'Clmshl',
                'long_name' => 'Clamshell',
            ],
            [
                'code' => 'UOM-POB',
                'short_name' => 'POB ',
                'long_name' => 'Pasta Oval Box',
            ],
        ];

        foreach ($conversionUnits as $value) {
            ItemUomModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name'],
            ]);
        }
    }
}
