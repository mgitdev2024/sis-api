<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('access_module_permissions', function (Blueprint $table) {
            $table->longText('allow_reopen')->nullable();
        });

        Schema::table('access_submodule_permissions', function (Blueprint $table) {
            $table->longText('allow_reopen')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_module_permissions', function (Blueprint $table) {
            $table->dropColumn('allow_reopen')->nullable();
        });

        Schema::table('access_submodule_permissions', function (Blueprint $table) {
            $table->dropColumn('allow_reopen')->nullable();
        });

    }
};
