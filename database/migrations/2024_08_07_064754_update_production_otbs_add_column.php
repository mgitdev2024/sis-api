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
        Schema::table('mos_production_otbs', function (Blueprint $table) {
            $table->string('delivery_type')->nullable()->change();
            $table->integer('produced_items_count')->default(0);
            $table->integer('received_items_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mos_production_otbs', function (Blueprint $table) {
            $table->string('delivery_type')->nullable(false)->change();
            $table->dropColumn(['produced_items_count', 'received_items_count']);
        });
    }
};
