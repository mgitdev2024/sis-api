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
        Schema::table('wms_stock_transfer_items', function (Blueprint $table) {
            $table->longText('transferred_items')->nullable();
            $table->longText('substandard_items')->nullable();
            $table->unsignedInteger('temporary_storage_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_stock_transfer_items', function (Blueprint $table) {
            // Dropping the columns in the rollback
            $table->dropColumn('transferred_items');
            $table->dropColumn('substandard_items');
            $table->unsignedInteger('temporary_storage_id');
        });
    }
};
