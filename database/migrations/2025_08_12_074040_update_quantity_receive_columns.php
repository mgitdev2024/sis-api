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
        Schema::table('store_receiving_inventory_items', function (Blueprint $table) {
            $table->float('order_quantity', 8, 2)->nullable()->change();
            $table->float('allocated_quantity', 8, 2)->nullable()->change();
            $table->float('received_quantity', 8, 2)->nullable()->change();
        });

        Schema::table('stock_inventory_items_count', function (Blueprint $table) {
            $table->float('system_quantity', 8, 2)->nullable()->change();
            $table->float('counted_quantity', 8, 2)->nullable()->change();
            $table->float('discrepancy_quantity', 8, 2)->nullable()->change();
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->float('quantity', 8, 2)->nullable()->change();
        });

        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->float('quantity', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_receiving_inventory_items', function (Blueprint $table) {
            $table->integer('order_quantity')->nullable()->change();
            $table->integer('allocated_quantity')->nullable()->change();
            $table->integer('received_quantity')->nullable()->change();
        });

        Schema::table('stock_inventory_items_count', function (Blueprint $table) {
            $table->integer('system_quantity')->nullable()->change();
            $table->integer('counted_quantity')->nullable()->change();
            $table->integer('discrepancy_quantity')->nullable()->change();
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->change();
        });

        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->change();
        });
    }
};
