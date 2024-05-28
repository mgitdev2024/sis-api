<?php

namespace Database\Seeders\MosSeeder;

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
                'short_name' => 'Pcs',
                'long_name' => 'Piece',
            ],
            [
                'short_name' => 'Pck',
                'long_name' => 'Pack',
            ],
            [
                'short_name' => 'Box',
                'long_name' => 'Box',
            ],
            [
                'short_name' => 'Set',
                'long_name' => 'Set',
            ],
            [
                'short_name' => 'Clmshl',
                'long_name' => 'Clamshell',
            ],
            [
                'short_name' => 'POB ',
                'long_name' => 'Pasta Oval Box',
            ],
        ];

        foreach ($conversionUnits as $value) {
            UomModel::create([
                'created_by_id' => $createdById,
                'short_name' => $value['short_name'],
                'long_name' => $value['long_name'],
            ]);
        }
    }
}
