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
        Schema::table('wms_stock_logs', function (Blueprint $table) {
            $table->integer('initial_stock')->nullable();
            $table->integer('final_stock')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_stock_logs', function (Blueprint $table) {
            $table->dropColumn(['initial_stock', 'final_stock']);
        });
    }
};
