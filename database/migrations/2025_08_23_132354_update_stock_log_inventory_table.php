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
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->float('quantity', 8, 2)->nullable()->change();
            $table->float('initial_stock', 8, 2)->nullable()->change();
            $table->float('final_stock', 8, 2)->nullable()->change();
        });

        Schema::table('stock_inventories', function (Blueprint $table) {
            $table->float('stock_count', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->change();
            $table->integer('initial_stock')->nullable()->change();
            $table->integer('final_stock')->nullable()->change();
        });
        Schema::table('stock_inventories', function (Blueprint $table) {
            $table->integer('stock_count')->nullable()->change();
        });
    }
};
