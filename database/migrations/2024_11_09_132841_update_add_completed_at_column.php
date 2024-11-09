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
        Schema::table('wms_warehouse_receiving', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable();
        });

        Schema::table('wms_warehouse_put_away', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable();
        });

        Schema::table('wms_stock_transfer_lists', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_warehouse_receiving', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });

        Schema::table('wms_warehouse_put_away', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });

        Schema::table('wms_stock_transfer_lists', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
