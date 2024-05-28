<?php

namespace Database\Seeders\MosSeeder;

use App\Models\Settings\StorageTypeModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Settings\Items\ItemCategoryModel;

class StorageTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $storageType = [
            [
                'name' => 'Ambient',
            ],
            [
                'name' => 'Chilled',
            ],
            [
                'name' => 'Frozen',
            ],
        ];
        $createdById = 1;

        foreach ($storageType as $value) {
            StorageTypeModel::create([
                'created_by_id' => $createdById,
                'name' => $value['name']
            ]);
        }
    }
}
