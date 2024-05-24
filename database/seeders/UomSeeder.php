<?php

namespace Database\Seeders;

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
        $createdById = 1;
        $conversionUnits = [
            [
                'short_uom' => 'Pcs',
                'long_uom' => 'Piece',
            ],
            [
                'short_uom' => 'Pck',
                'long_uom' => 'Pack',
            ],
            [
                'short_uom' => 'Box',
                'long_uom' => 'Box',
            ],
            [
                'short_uom' => 'Set',
                'long_uom' => 'Set',
            ],
            [
                'short_uom' => 'Clmshl',
                'long_uom' => 'Clamshell',
            ],
            [
                'short_uom' => 'POB ',
                'long_uom' => 'Pasta Oval Box',
            ],
        ];

        foreach ($conversionUnits as $value) {
            UomModel::create([
                'created_by_id' => $createdById,
                'short_uom' => $value['short_uom'],
                'long_uom' => $value['long_uom'],
            ]);
        }
    }
}
