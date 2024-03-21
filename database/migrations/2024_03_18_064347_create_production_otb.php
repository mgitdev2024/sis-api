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
        Schema::create('production_otb', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->string('delivery_type');
            $table->string('item_code');
            $table->integer('requested_quantity');
            $table->float('buffer_level');
            $table->float('plotted_quantity');
            $table->integer('actual_quantity')->default(0);

            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('credentials');
            $table->foreign('updated_by_id')->references('id')->on('credentials');
            $table->foreign('production_order_id')->references('id')->on('production_orders');
            $table->foreign('item_code')->references('item_code')->on('item_masterdata');
            $table->foreign('delivery_type')->references('type')->on('delivery_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_otb');
    }
};
