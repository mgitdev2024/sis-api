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
        Schema::create('item_masterdata', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->index();
            $table->string('description');
            $table->unsignedBigInteger('item_classification_id');
            $table->unsignedBigInteger('item_variant_type_id');
            $table->unsignedBigInteger('uom_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('sub_category_id');
            $table->unsignedBigInteger('storage_type_id');
            $table->unsignedBigInteger('stock_type_id');
            $table->unsignedBigInteger('item_movement_id');
            $table->integer('delivery_lead_time');
            $table->integer('re_order_level');
            $table->integer('stock_rotation_type');
            $table->integer('qty_per_pallet');
            $table->string('dimension');
            $table->integer('is_qa_required');
            $table->integer('is_qa_disposal');
            $table->string('image');
            $table->integer('primary_item_packing_size');
            $table->unsignedBigInteger('primary_conversion_id');
            $table->integer('secondary_item_packing_size');
            $table->unsignedBigInteger('secondary_conversion_id');
            $table->integer('shelf_life');
            $table->unsignedBigInteger('plant_id');
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->foreign('created_by_id')->references('id')->on('credentials');
            $table->foreign('updated_by_id')->references('id')->on('credentials');
            $table->foreign('item_classification_id')->references('id')->on('item_classifications')->onDelete('restrict');
            $table->foreign('item_variant_type_id')->references('id')->on('item_variant_types')->onDelete('restrict');
            $table->foreign('plant_id')->references('id')->on('plants')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('uom')->onDelete('restrict');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('restrict');
            $table->foreign('storage_type_id')->references('id')->on('storage_type')->onDelete('restrict');
            $table->foreign('stock_type_id')->references('id')->on('stock_type')->onDelete('restrict');
            $table->foreign('item_movement_id')->references('id')->on('item_movement')->onDelete('restrict');
            $table->foreign('primary_conversion_id')->references('id')->on('conversions')->onDelete('restrict');
            $table->foreign('secondary_conversion_id')->references('id')->on('conversions')->onDelete('restrict');
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_masterdata');
    }
};
