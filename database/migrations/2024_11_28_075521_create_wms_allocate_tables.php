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
        Schema::create('wms_allocation_orders', function (Blueprint $table) {
            $table->id();
            $table->string('consolidation_reference_number');
            $table->date('estimated_delivery_date');
            $table->string('delivery_type_code');
            $table->string('consolidated_by');
            SchemaHelper::addCommonColumns($table, 0); // 0 = for allocation, 1 = allocated, 2 = picklist
        });
        Schema::create('wms_allocation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allocation_order_id');
            $table->unsignedBigInteger('item_id');
            // $table->string('request_type');
            $table->integer('theoretical_soh');
            $table->integer('total_order_quantity');
            $table->longText('store_order_details');
            $table->integer('excess_stocks');
            $table->integer('allocated_stocks');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('item_id')->references('id')->on('wms_item_masterdata');
            $table->foreign('allocation_order_id')->references('id')->on('wms_allocation_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wms_allocation_orders');
        Schema::dropIfExists('wms_allocation_items');
    }
};
