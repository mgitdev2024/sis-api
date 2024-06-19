<?php

namespace Database\Seeders;

use App\Models\Access\ModulePermissionModel;
use App\Models\Access\ScmSystemModel;
use Illuminate\Database\Seeder;
use App\Models\CredentialModel;

class ModulePermissionSeeder extends Seeder
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
                'scm_system_id' => 1,
                'name' => 'Supply Planner',
                'code' => 'SCM-SUP-PLA',
                'description' => 'Module for Supply Planner. Initial step for SCM-SUP-PLA',
                'is_enabled' => ["0001", "0000", 5182, 5358, 6208, 6233],
                'allow_view' => ["0001", "0000", 5182, 5358, 6208, 6233],
                'allow_create' => ["0001", "0000", 5182, 5358, 6208, 6233],
                'allow_update' => ["0001", "0000", 5182, 5358, 6208, 6233],
                'allow_delete' => ["0001", "0000", 5182, 5358, 6208, 6233],
            ],
            [
                'scm_system_id' => 1,
                'name' => 'Team Leader',
                'code' => 'SCM-TEM-LEA',
                'description' => 'Module for Team Leader.',
                'is_enabled' => ["0000", 5182, 5358, 6208, 6233],
                'allow_view' => ["0000", 5182, 5358, 6208, 6233],
                'allow_create' => ["0000", 5182, 5358, 6208, 6233],
                'allow_update' => ["0000", 5182, 5358, 6208, 6233],
                'allow_delete' => ["0000", 5182, 5358, 6208, 6233],
            ],
            [
                'scm_system_id' => 1,
                'name' => 'Line Staff',
                'code' => 'SCM-LIN-STA',
                'description' => 'Module for Line Staff.',
                'is_enabled' => ["0000", 5182, 5358, 6208, 6233],
                'allow_view' => ["0000", 5182, 5358, 6208, 6233],
                'allow_create' => ["0000", 5182, 5358, 6208, 6233],
                'allow_update' => ["0000", 5182, 5358, 6208, 6233],
                'allow_delete' => ["0000", 5182, 5358, 6208, 6233],
            ],
            [
                'scm_system_id' => 1,
                'name' => 'Quality Assurance',
                'code' => 'SCM-QUA-ASS',
                'description' => 'Module for Quality Assurance.',
                'is_enabled' => ["0000", 5182, 5358, 6208, 6233],
                'allow_view' => ["0000", 5182, 5358, 6208, 6233],
                'allow_create' => ["0000", 5182, 5358, 6208, 6233],
                'allow_update' => ["0000", 5182, 5358, 6208, 6233],
                'allow_delete' => ["0000", 5182, 5358, 6208, 6233],
            ],
            [
                'scm_system_id' => 1,
                'name' => 'Warehouse',
                'code' => 'SCM-WAR-HSE',
                'description' => 'Module for Warehouse.',
                'is_enabled' => ["0000", 5182, 5358, 6208, 6233],
                'allow_view' => ["0000", 5182, 5358, 6208, 6233],
                'allow_create' => ["0000", 5182, 5358, 6208, 6233],
                'allow_update' => ["0000", 5182, 5358, 6208, 6233],
                'allow_delete' => ["0000", 5182, 5358, 6208, 6233],
            ],
        ];

        foreach ($scmSystem as $value) {
            ModulePermissionModel::create([
                'scm_system_id' => $value['scm_system_id'],
                'name' => $value['name'],
                'code' => $value['code'],
                'is_enabled' => json_encode($value['is_enabled']),
                'allow_view' => json_encode($value['allow_view']),
                'allow_create' => json_encode($value['allow_create']),
                'allow_update' => json_encode($value['allow_update']),
                'allow_delete' => json_encode($value['allow_delete']),
                'description' => json_encode($value['description']),
                'created_by_id' => $createdById,
            ]);
        }
    }
}
