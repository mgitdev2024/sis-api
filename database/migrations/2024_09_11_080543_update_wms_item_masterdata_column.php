<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the column schema
        Schema::table('wms_item_masterdata', function (Blueprint $table) {
            $table->text('parent_item_id')->nullable()->change();
        });

        // Convert the value from '1' to '[1]' in the 'parent_item_id' column
        DB::table('wms_item_masterdata')
            ->whereNotNull('parent_item_id')
            ->update(['parent_item_id' => DB::raw("JSON_ARRAY(CAST(parent_item_id AS UNSIGNED))")]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the changes in the column schema
        Schema::table('wms_item_masterdata', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_item_id')->nullable()->change();
        });

        // Convert '[1]' back to '1' if needed
        DB::table('wms_item_masterdata')
            ->where('parent_item_id', '[1]')
            ->update(['parent_item_id' => 1]);
    }
};
