<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\WMS\Settings\ItemMasterData\ItemConversionModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
                'code' => 'CON-PCS',
                'short_name' => 'Pcs',
                'long_name' => 'Pieces',
            ],
            [
                'code' => 'CON-BOX',
                'short_name' => 'Box',
                'long_name' => 'Box',
            ],
            [
                'code' => 'CON-CLM',
                'short_name' => 'Clmshl',
                'long_name' => 'Clamshell',
            ],
            [
                'code' => 'CON-POB',
                'short_name' => 'POB',
                'long_name' => 'Pasta Oval Box',
            ],
        ];

        foreach ($conversionUnits as $value) {
            ItemConversionModel::create([
                'created_by_id' => $createdById,
                'code' => $value['code'],
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name'],
            ]);
        }
    }
}
