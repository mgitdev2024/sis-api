<?php

use App\Helpers\SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_module_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('description')->nullable();
            $table->text('is_enabled')->nullable();
            $table->text('allow_view')->nullable();
            $table->text('allow_create')->nullable();
            $table->text('allow_update')->nullable();
            $table->text('allow_delete')->nullable();
            $table->text('allow_reopen')->nullable();
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('access_submodule_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_permission_id');
            $table->string('name');
            $table->string('code');
            $table->string('description')->nullable();
            $table->text('is_enabled')->nullable();
            $table->text('allow_view')->nullable();
            $table->text('allow_create')->nullable();
            $table->text('allow_update')->nullable();
            $table->text('allow_delete')->nullable();
            $table->text('allow_reopen')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('module_permission_id')->references('id')->on('access_module_permissions');
        });

        #region Module Data
        $modules = [
            [
                'name' => 'Dashboard',
                'code' => 'SIS-DSH-BRD',
                'description' => 'Store Inventory Dashboard',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'name' => 'Store Receiving',
                'code' => 'SIS-STR-REC',
                'description' => 'Store Receiving Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'name' => 'Inventory',
                'code' => 'SIS-STR-INV',
                'description' => 'Store Inventory Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'name' => 'Direct Purchase',
                'code' => 'SIS-DIR-PUR',
                'description' => 'Direct Purchase Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'name' => 'Settings',
                'code' => 'SIS-STR-SET',
                'description' => 'Settings Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
        ];
        DB::table('access_module_permissions')->insert($modules);
        #endregion

        #region Sub Module Data
        $subModules = [
            [
                'module_permission_id' => 1,
                'name' => 'Dashboard',
                'code' => 'SIS-SUB-DSH-BRD',
                'description' => 'Store Inventory Dashboard Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 1,
                'name' => 'Reports',
                'code' => 'SIS-SUB-STR-REP',
                'description' => 'Store Inventory Reports Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 1,
                'name' => 'Store Filters',
                'code' => 'SIS-SUB-STR-FIL',
                'description' => 'Store Inventory Store Filters Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 1,
                'name' => 'Report Filters',
                'code' => 'SIS-SUB-REP-FIL',
                'description' => 'Store Inventory Report Filters Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 3,
                'name' => 'Item List',
                'code' => 'SIS-INV-ITM-LST',
                'description' => 'Store Inventory Item List Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 3,
                'name' => 'Inventory Out',
                'code' => 'SIS-INV-ITM-OUT',
                'description' => 'Store Inventory Item Out Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 3,
                'name' => 'Stock Transfer',
                'code' => 'SIS-INV-STK-TRF',
                'description' => 'Store Inventory Stock Transfer Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 3,
                'name' => 'Stock Count',
                'code' => 'SIS-INV-STK-CNT',
                'description' => 'Store Inventory Stock Count Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 3,
                'name' => 'Store Filters',
                'code' => 'SIS-SUB-STR-FIL-INV',
                'description' => 'Store Inventory Store Filters Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 3,
                'name' => 'Stock Count Month End',
                'code' => 'SIS-INV-STK-CNT-ME',
                'description' => 'Store Inventory Stock Count Month End Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 4,
                'name' => 'Store Filters',
                'code' => 'SIS-SUB-STR-FIL-DIR',
                'description' => 'Store Inventory Store Filters Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
            [
                'module_permission_id' => 5,
                'name' => 'Store Settings',
                'code' => 'SIS-SUB-STR-SET',
                'description' => 'Store Inventory Store Settings Sub Module',
                'is_enabled' => '[]',
                'allow_view' => '[]',
                'allow_create' => '[]',
                'allow_update' => '[]',
                'allow_delete' => '[]',
                'allow_reopen' => '[]',
                'created_by_id' => '0000',
                'created_at' => now()
            ],
        ];
        DB::table('access_submodule_permissions')->insert($subModules);
        #endregion
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_module_permissions');
        Schema::dropIfExists('access_submodule_permissions');
    }
};
