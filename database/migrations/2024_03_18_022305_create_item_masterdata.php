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
            $table->unsignedBigInteger('conversion_id');
            $table->integer('primary_item_packing_size');
            $table->integer('secondary_item_packing_size');
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
