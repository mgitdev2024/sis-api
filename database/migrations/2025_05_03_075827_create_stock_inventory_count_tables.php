<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\SchemaHelper;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_inventory_count', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number'); // SC00001
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            SchemaHelper::addCommonColumns($table, 0); // 0 = Pending, 1 = For Review, 2 = Posted
        });

        Schema::create('stock_inventory_items_count', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_inventory_count_id'); // FK to stock_inventory_count
            $table->string('item_code'); // CR 12
            $table->string('item_description'); // Cheeseroll Box of 12
            $table->string('item_category_name');
            $table->integer('system_quantity'); // 12
            $table->integer('counted_quantity'); // 12
            $table->integer('discrepancy_quantity'); // 0
            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_inventory_count');
        Schema::dropIfExists('stock_inventory_items_count');
    }
};
