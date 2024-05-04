<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings\Measurements\ConversionModel;

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
                'conversion_short_uom' => 'Pcs',
                'conversion_long_uom' => 'Pieces',
            ],
            [
                'conversion_short_uom' => 'Pcs',
                'conversion_long_uom' => 'Pieces',
            ],
            [
                'conversion_short_uom' => 'Pcs',
                'conversion_long_uom' => 'Pieces',
            ],
            [
                'conversion_short_uom' => 'Pcs',
                'conversion_long_uom' => 'Pieces',
            ],
        ];

        foreach ($conversionUnits as $value) {
            ConversionModel::create([
                'created_by_id' => $createdById,
                'conversion_short_uom' => $value['conversion_short_uom'],
                'conversion_long_uom' => $value['conversion_long_uom'],
            ]);
        }
    }
}
