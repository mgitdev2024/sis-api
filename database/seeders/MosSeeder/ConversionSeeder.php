<?php

namespace Database\Seeders\MosSeeder;

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
                'conversion_short_name' => 'Pcs',
                'conversion_long_name' => 'Pieces',
            ],
            [
                'conversion_short_name' => 'Box',
                'conversion_long_name' => 'Box',
            ],
            [
                'conversion_short_name' => 'Clmshl',
                'conversion_long_name' => 'Clamshell',
            ],
            [
                'conversion_short_name' => 'POB',
                'conversion_long_name' => 'Pasta Oval Box',
            ],
        ];

        foreach ($conversionUnits as $value) {
            ConversionModel::create([
                'created_by_id' => $createdById,
                'conversion_short_name' => $value['conversion_short_name'],
                'conversion_long_name' => $value['conversion_long_name'],
            ]);
        }
    }
}
