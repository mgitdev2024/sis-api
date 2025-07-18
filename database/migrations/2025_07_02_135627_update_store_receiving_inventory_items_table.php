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
            $table->string('order_session_id')->nullable();
            $table->string('completed_by_id')->nullable();
            $table->datetime('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_receiving_inventory_items', function (Blueprint $table) {
            $table->dropColumn('order_session_id');
            $table->dropColumn('completed_by_id');
            $table->dropColumn('completed_at');
        });
    }
};
