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
            $table->tinyInteger('is_add_ons')->default(0); // 0 = false, 1 = true
            $table->text('add_ons_items')->nullable();
            $table->tinyInteger('order_type')->nullable(); // 0 = regular, 1 = advanced, 2 = reservation, 4 = all
            $table->tinyInteger('delivery_type_id')->nullable();
            $table->longText('orderable_by')->nullable();
            $table->tinyInteger('show_stocks')->default(0);
            $table->tinyInteger('order_with_zero_stocks')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_item_masterdata', function (Blueprint $table) {
            $table->dropColumn('is_add_ons');
            $table->dropColumn('add_ons_items');
            $table->dropColumn('order_type');
            $table->dropColumn('delivery_type_id');
            $table->dropColumn('orderable_by');
            $table->dropColumn('show_stocks');
            $table->dropColumn('order_with_zero_stocks');
        });
    }
};


