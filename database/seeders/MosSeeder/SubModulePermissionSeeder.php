<?php

namespace Database\Seeders\MosSeeder;

use App\Models\Access\ScmSystemModel;
use App\Models\Access\SubModulePermissionModel;
use Illuminate\Database\Seeder;
use App\Models\CredentialModel;

class SubModulePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdById = 1;
        $createdById = 1;
        $scmSystem = [
            [
                'module_permission_id' => 2,
                'name' => 'Team Leader OTA',
                'code' => 'SCM-TEM-LEA-OTA',
                'description' => 'Submodule of Team Leader OTA',
                'is_enabled' => ["0000", 5182, 5358, 6208, 6233],
                'allow_view' => ["0000", 5182, 5358, 6208, 6233],
                'allow_create' => ["0000", 5182, 5358, 6208, 6233],
                'allow_update' => ["0000", 5182, 5358, 6208, 6233],
                'allow_delete' => ["0000", 5182, 5358, 6208, 6233],
            ],
            [
                'module_permission_id' => 2,
                'name' => 'Team Leader OTB',
                'code' => 'SCM-TEM-PLA-OTB',
                'description' => 'Submodule of Team Leader OTB',
                'is_enabled' => ["0000", 5182, 5358, 6208, 6233],
                'allow_view' => ["0000", 5182, 5358, 6208, 6233],
                'allow_create' => ["0000", 5182, 5358, 6208, 6233],
                'allow_update' => ["0000", 5182, 5358, 6208, 6233],
                'allow_delete' => ["0000", 5182, 5358, 6208, 6233],
            ],
            [
                'module_permission_id' => 3,
                'name' => 'Line Staff OTA',
                'code' => 'SCM-LIN-STA-OTA',
                'description' => 'Submodule of Line Staff OTA',
                'is_enabled' => ["0000", 5182, 5358, 6208, 6233],
                'allow_view' => ["0000", 5182, 5358, 6208, 6233],
                'allow_create' => ["0000", 5182, 5358, 6208, 6233],
                'allow_update' => ["0000", 5182, 5358, 6208, 6233],
                'allow_delete' => ["0000", 5182, 5358, 6208, 6233],
            ],
            [
                'module_permission_id' => 3,
                'name' => 'Line Staff OTB',
                'code' => 'SCM-LIN-STA-OTB',
                'description' => 'Submodule of Line Staff OTB',
                'is_enabled' => ["0000", 5182, 5358, 6208, 6233],
                'allow_view' => ["0000", 5182, 5358, 6208, 6233],
                'allow_create' => ["0000", 5182, 5358, 6208, 6233],
                'allow_update' => ["0000", 5182, 5358, 6208, 6233],
                'allow_delete' => ["0000", 5182, 5358, 6208, 6233],
            ],
        ];

        foreach ($scmSystem as $value) {
            SubModulePermissionModel::create([
                'module_permission_id' => $value['module_permission_id'],
                'name' => $value['name'],
                'code' => $value['code'],
                'description' => json_encode($value['description']),
                'is_enabled' => json_encode($value['is_enabled']),
                'allow_view' => json_encode($value['allow_view']),
                'allow_create' => json_encode($value['allow_create']),
                'allow_update' => json_encode($value['allow_update']),
                'allow_delete' => json_encode($value['allow_delete']),
                'created_by_id' => $createdById,
            ]);
        }
    }
}
