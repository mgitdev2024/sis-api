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
            $table->datetime('received_at')->nullable();
            $table->string('received_by_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_receiving_inventory_items', function (Blueprint $table) {
            $table->dropColumn([
                'received_at',
                'received_by_id'
            ]);
        });
    }
};
