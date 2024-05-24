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
        Schema::create('warehouse_receiving', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->unsignedBigInteger('production_order_id');
            $table->integer('batch_number');
            $table->string('item_code');
            $table->longText('produced_items');
            $table->integer('quantity');
            $table->string('sku_type');
            $table->tinyInteger('status')->default(0); // 0 = not yet received, 1 = received
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->foreign('production_order_id')->references('id')->on('production_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_receiving');
    }
};
