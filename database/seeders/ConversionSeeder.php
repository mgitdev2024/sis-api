<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Measurements\Conversion;

class ConversionSeeder extends Seeder
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
                'primary_short_uom' => 'Pcs',
                'secondary_short_uom' => 'Pcs/Box',
                'primary_long_uom' => 'Pieces',
                'secondary_long_uom' => 'Pieces/Box'
            ],
            [
                'primary_short_uom' => 'Pcs',
                'secondary_short_uom' => 'Box',
                'primary_long_uom' => 'Pieces',
                'secondary_long_uom' => 'Box'
            ],
            [
                'primary_short_uom' => 'Pcs',
                'secondary_short_uom' => 'Pck',
                'primary_long_uom' => 'Pieces',
                'secondary_long_uom' => 'Pack'
            ],
            [
                'primary_short_uom' => 'Pcs',
                'secondary_short_uom' => 'Set',
                'primary_long_uom' => 'Pieces',
                'secondary_long_uom' => 'Set'
            ],
        ];

        foreach ($conversionUnits as $value) {
            Conversion::create([
                'created_by_id' => $createdById,
                'primary_short_uom' => $value['primary_short_uom'],
                'primary_long_uom' => $value['primary_long_uom'],
                'secondary_short_uom' => $value['secondary_short_uom'],
                'secondary_long_uom' => $value['secondary_long_uom'],
            ]);
        }
    }
}
