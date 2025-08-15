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
        Schema::table('stock_inventory_count', function (Blueprint $table) {
            $table->datetime('reviewed_at')->nullable();
            $table->string('reviewed_by_id')->nullable();
            $table->datetime('posted_at')->nullable();
            $table->string('posted_by_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_inventory_count', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
            $table->dropColumn('reviewed_by_id');
            $table->dropColumn('posted_at');
            $table->dropColumn('posted_by_id');
        });
    }
};
