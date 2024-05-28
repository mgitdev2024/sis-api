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
        Schema::create('wms_item_masterdata', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique()->index();
            $table->string('description');
            $table->unsignedBigInteger('item_category_id')->nullable();
            $table->unsignedBigInteger('item_classification_id')->nullable();
            $table->unsignedBigInteger('item_variant_type_id')->nullable();
            $table->unsignedBigInteger('parent_item_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->unsignedBigInteger('storage_type_id')->nullable();
            $table->unsignedBigInteger('stock_type_id')->nullable();
            $table->unsignedBigInteger('item_movement_id')->nullable();
            $table->integer('delivery_lead_time')->nullable();
            $table->integer('re_order_level')->nullable();
            $table->integer('stock_rotation_type')->nullable();
            $table->integer('qty_per_pallet')->nullable();
            $table->string('dimension')->nullable();
            $table->integer('is_qa_required')->nullable();
            $table->integer('is_qa_disposal')->nullable();
            $table->string('attachment')->nullable();
            $table->integer('primary_item_packing_size')->nullable();
            $table->unsignedBigInteger('primary_conversion_id')->nullable();
            $table->integer('secondary_item_packing_size')->nullable();
            $table->unsignedBigInteger('secondary_conversion_id')->nullable();
            $table->integer('ambient_shelf_life')->nullable();
            $table->integer('chilled_shelf_life')->nullable();
            $table->integer('frozen_shelf_life')->nullable();
            $table->text('consumer_instructions')->nullable();

            $table->unsignedBigInteger('plant_id');
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();


            $table->foreign('item_category_id')->references('id')->on('wms_item_categories')->onDelete('restrict');
            $table->foreign('item_classification_id')->references('id')->on('wms_item_classifications')->onDelete('restrict');
            $table->foreign('item_variant_type_id')->references('id')->on('wms_item_variant_types')->onDelete('restrict');
            $table->foreign('plant_id')->references('id')->on('wms_storage_facility_plants')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('wms_item_uoms')->onDelete('restrict');
            $table->foreign('storage_type_id')->references('id')->on('wms_storage_types')->onDelete('restrict');
            $table->foreign('stock_type_id')->references('id')->on('wms_item_stock_types')->onDelete('restrict');
            $table->foreign('item_movement_id')->references('id')->on('wms_item_movements')->onDelete('restrict');
            $table->foreign('primary_conversion_id')->references('id')->on('wms_item_conversions')->onDelete('restrict');
            $table->foreign('secondary_conversion_id')->references('id')->on('wms_item_conversions')->onDelete('restrict');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wms_item_masterdata');
    }
};
