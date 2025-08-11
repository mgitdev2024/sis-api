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
            $table->string('remarks')->nullable();
        });
        Schema::table('stock_inventory_count', function (Blueprint $table) {
            $table->string('remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_receiving_inventory_items', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
        Schema::table('stock_inventory_count', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
