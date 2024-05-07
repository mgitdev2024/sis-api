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
        Schema::create('production_batch', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_otb_id')->nullable();
            $table->unsignedBigInteger('production_ota_id')->nullable();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('produced_item_id')->nullable();
            $table->string('batch_code');
            $table->integer('batch_number');
            $table->tinyInteger('batch_type');
            $table->string('quantity');
            $table->integer('actual_quantity')->default(0);
            $table->integer('actual_secondary_quantity')->default(0);
            $table->date('chilled_exp_date')->nullable();
            $table->date('frozen_exp_date')->nullable();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->tinyInteger('is_printed')->default(0); // 0 = Not Printed, 1 = Printed
            $table->tinyInteger('status')->default(0); // 0 = In Progress, 1 = On Hold, 2 = Complete
            $table->timestamps();



            $table->foreign('production_otb_id')->references('id')->on('production_otb');
            $table->foreign('production_ota_id')->references('id')->on('production_ota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batch');
    }
};
