<?php

use App\Helpers\SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number'); // DP-0000001
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            $table->string('batch_code')->nullable();
            $table->string('item_code'); // CR 12
            $table->string('item_description'); // Cheeseroll Box of 12
            $table->string('item_category_name'); // Breads
            $table->integer('quantity');
            SchemaHelper::addCommonColumns($table, 0); // 0 = Pending, 1 = Closed / Complete
        });

        Schema::create('stock_conversion_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_conversion_id');
            $table->string('item_code'); // CR 12
            $table->string('item_description'); // Cheeseroll Box of 12
            $table->string('item_category_name');
            $table->integer('quantity');
            $table->integer('converted_quantity');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('stock_conversion_id')->references('id')->on('stock_conversions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_conversions');
        Schema::dropIfExists('stock_conversion_items');
    }
};
