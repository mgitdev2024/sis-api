<?php

namespace Database\Seeders;

use App\Models\Access\SubModulePermissionModel;
use App\Models\Admin\System\AdminSystemModel;
use Illuminate\Database\Seeder;

class AdminSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdById = '0000';
        $adminSystem = [
            'name' => 'Store Inventory System',
            'code' => 'SIS',
            'description' => 'Store Inventory System of Mary Grace',
            'created_by_id' => 0000
        ];

        foreach ($adminSystem as $value) {
            AdminSystemModel::create([
                'name' => $value['name'],
                'code' => $value['code'],
                'description' => $value['description'],
                'created_by_id' => $createdById,
            ]);
        }
    }
}
