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
        Schema::table('wms_item_masterdata', function (Blueprint $table) {
            $table->tinyInteger('picking_type')->default(0); // 0 = discreet, 1 = batch
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_item_masterdata', function (Blueprint $table) {
            $table->dropColumn('picking_type');
        });
    }
};
