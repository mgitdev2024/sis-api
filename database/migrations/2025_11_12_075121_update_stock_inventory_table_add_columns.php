<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_inventories', function (Blueprint $table) {
            $table->boolean('is_base_unit')->default(0);
            $table->string('uom')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_inventories', function (Blueprint $table) {
            $table->dropColumn('is_base_unit');
            $table->dropColumn('uom');
        });
    }
};
