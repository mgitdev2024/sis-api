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
        Schema::table('mos_production_otas', function (Blueprint $table) {
            // Drop the foreign key constraint for item_code
            $table->dropForeign(['item_code']);
        });
        Schema::table('mos_production_otbs', function (Blueprint $table) {
            // Drop the foreign key constraint for item_code
            $table->dropForeign(['item_code']);
        });
        Schema::table('wms_stock_inventories', function (Blueprint $table) {
            // Drop the foreign key constraint for item_code
            $table->dropForeign(['item_code']);
        });
        Schema::table('wms_stock_logs', function (Blueprint $table) {
            // Drop the foreign key constraint for item_code
            $table->dropForeign(['item_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mos_production_otbs', function (Blueprint $table) {
            $table->foreign('item_code')->references('item_code')->on('wms_item_masterdata');
        });
        Schema::table('mos_production_otas', function (Blueprint $table) {
            $table->foreign('item_code')->references('item_code')->on('wms_item_masterdata');
        });
        Schema::table('wms_stock_inventories', function (Blueprint $table) {
            $table->foreign('item_code')->references('item_code')->on('wms_item_masterdata');
        });
        Schema::table('wms_stock_logs', function (Blueprint $table) {
            $table->foreign('item_code')->references('item_code')->on('wms_item_masterdata');
        });
    }
};
