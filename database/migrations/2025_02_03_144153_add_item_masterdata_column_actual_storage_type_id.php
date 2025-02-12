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
        Schema::table('wms_item_masterdata', function (Blueprint $table) {
            $table->tinyInteger('secondary_storage_type_id')->nullable();
        });
        // Set the initial value of secondary_storage_type_id to match storage_type_id
        DB::statement('UPDATE wms_item_masterdata SET secondary_storage_type_id = storage_type_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_item_masterdata', function (Blueprint $table) {
            $table->dropColumn('secondary_storage_type_id');
        });
    }
};
