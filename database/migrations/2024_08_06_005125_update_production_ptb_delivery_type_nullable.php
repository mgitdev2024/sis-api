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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mos_production_otbs', function (Blueprint $table) {
            $table->string('delivery_type')->nullable(false)->change();
        });
    }
};
