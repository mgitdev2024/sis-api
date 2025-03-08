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
            $table->tinyInteger('is_transmittal_pushed')->default(0);
            $table->string('transmittal_pushed_by')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_warehouse_receiving', function (Blueprint $table) {
            $table->dropColumn('is_transmittal_pushed');
            $table->dropColumn('transmittal_pushed_by');

        });
    }
};
