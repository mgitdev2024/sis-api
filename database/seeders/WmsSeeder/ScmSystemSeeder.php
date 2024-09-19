<?php

namespace Database\Seeders\WmsSeeder;

use App\Models\Admin\System\ScmSystemModel;
use Illuminate\Database\Seeder;

class ScmSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdById = 0000;
        $scmSystem = [
            [
                'name' => 'Manufacturing Operations System',
                'code' => 'SCM-MOS',
            ],
            [
                'name' => 'Warehouse Management System',
                'code' => 'SCM-WMS',
            ],
        ];

        foreach ($scmSystem as $value) {
            ScmSystemModel::create([
                'name' => $value['name'],
                'code' => $value['code'],
                'created_by_id' => $createdById,
            ]);
        }
    }
}
