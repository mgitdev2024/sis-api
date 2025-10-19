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
        Schema::table('store_consolidation_cache', function (Blueprint $table) {
            $table->integer('consolidated_order_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_consolidation_cache', function (Blueprint $table) {
            $table->dropColumn('consolidated_order_id');
        });
    }
};
