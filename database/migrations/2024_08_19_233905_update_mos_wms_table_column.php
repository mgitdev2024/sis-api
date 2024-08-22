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
        Schema::table('mos_production_otas', function (Blueprint $table) {
            $table->integer('in_qa_count')->default(0);
        });
        Schema::table('mos_production_otbs', function (Blueprint $table) {
            $table->integer('in_qa_count')->default(0);
        });
        Schema::table('wms_warehouse_receiving', function (Blueprint $table) {
            $table->integer('temporary_storage_id')->nullable();
        });
        Schema::table('wms_warehouse_put_away', function (Blueprint $table) {
            $table->dropForeign(['temporary_storage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mos_production_otas', function (Blueprint $table) {
            $table->dropColumn('in_qa_count');
        });
        Schema::table('mos_production_otbs', function (Blueprint $table) {
            $table->dropColumn('in_qa_count');
        });
        Schema::table('wms_warehouse_receiving', function (Blueprint $table) {
            $table->dropForeign(['temporary_storage_id']);
        });
    }
};
