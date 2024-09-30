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
        Schema::table('wms_warehouse_put_away', function (Blueprint $table) {
            $table->dropColumn('item_code');
            $table->unsignedBigInteger('item_id');
        });

        Schema::table('wms_warehouse_for_put_away', function (Blueprint $table) {
            $table->dropColumn('item_code');
            $table->unsignedBigInteger('item_id');
        });

        Schema::table('wms_stock_inventories', function (Blueprint $table) {
            $table->dropColumn('item_code');
            $table->unsignedBigInteger('item_id');
        });

        Schema::table('wms_stock_logs', function (Blueprint $table) {
            $table->dropColumn('item_code');
            $table->unsignedBigInteger('item_id');
        });

        Schema::table('wms_stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn('item_code');
            $table->unsignedBigInteger('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_warehouse_put_away', function (Blueprint $table) {
            $table->dropColumn('item_id');
            $table->string('item_code');
        });

        Schema::table('wms_stock_inventories', function (Blueprint $table) {
            $table->dropColumn('item_id');
            $table->string('item_code');
        });

        Schema::table('wms_stock_logs', function (Blueprint $table) {

            $table->dropColumn('item_id');
            $table->string('item_code');
        });

        Schema::table('wms_stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn('item_id');
            $table->string('item_code');
        });
    }
};
