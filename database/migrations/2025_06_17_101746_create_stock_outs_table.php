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
        Schema::create('stock_outs', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number'); // SO-6000001
            $table->string('or_number'); // 8000001
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            $table->date('stock_out_date'); // 2025-06-17
            $table->text('attachment')->nullable();
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('stock_out_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_out_id');
            $table->string('item_code'); // CR 12
            $table->string('item_description'); // Cheeseroll Box of 12
            $table->string('item_category_name');
            $table->string('unit_of_measure'); // Box
            $table->string('item_variant_name'); // Whole
            $table->integer('quantity'); // 12
            SchemaHelper::addCommonColumns($table);

            $table->foreign('stock_out_id')->references('id')->on('stock_outs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_out_items');
        Schema::dropIfExists('stock_outs');
    }
};
