<?php

namespace Database\Seeders;

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
        $adminSystem = [
            'name' => 'Store Inventory System',
            'code' => 'SIS',
            'description' => 'Store Inventory System of Mary Grace',
            'created_by_id' => '0000'
        ];

        AdminSystemModel::create($adminSystem);

    }
}
