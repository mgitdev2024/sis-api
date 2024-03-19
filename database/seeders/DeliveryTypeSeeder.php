<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Delivery\DeliveryType;

class DeliveryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdById = 1;
        $classifications = [
            [
                'type' => '1D',
                'description' => 'First Delivery'
            ],
            [
                'type' => '2D',
                'description' => 'Second Delivery'
            ],
            [
                'type' => '3D',
                'description' => 'Third Delivery'
            ],
        ];

        foreach ($classifications as $value) {
            DeliveryType::create([
                'created_by_id' => $createdById,
                'type' => $value['type'],
                'description' => $value['description'],
            ]);
        }
    }
}
