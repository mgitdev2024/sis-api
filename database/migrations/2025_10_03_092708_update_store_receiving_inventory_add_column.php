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
        Schema::table('store_receiving_inventory', function (Blueprint $table) {
            $table->boolean('is_sap_created')->nullable(); // 0 = false, 1 = true
        });

        Schema::table('store_receiving_inventory_items', function (Blueprint $table) {
            $table->string('goods_issue_uuid')->nullable(); // 0 = false, 1 = true
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_receiving_inventory', function (Blueprint $table) {
            $table->dropColumn('is_sap_created');
        });
        Schema::table('store_receiving_inventory_items', function (Blueprint $table) {
            $table->dropColumn('goods_issue_uuid');
        });
    }
};
