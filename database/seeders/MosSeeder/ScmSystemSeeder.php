<?php

namespace Database\Seeders\MosSeeder;

use App\Models\Access\ScmSystemModel;
use Illuminate\Database\Seeder;
use App\Models\CredentialModel;

class ScmSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdById = 1;
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
