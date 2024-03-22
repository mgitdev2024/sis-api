<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings\Items\ItemClassificationModel;

class ItemClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $classifications = ['Breads', 'Cakes', 'Pastries', 'Loaves', 'Others'];
        $createdById = 1;

        foreach ($classifications as $name) {
            ItemClassificationModel::create([
                'created_by_id' => $createdById,
                'name' => $name,
            ]);
        }
    }
}
