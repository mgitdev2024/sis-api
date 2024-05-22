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
        Schema::create('archived_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->integer('batch_number');
            $table->tinyInteger('production_type'); // 0 = otb, 1 = ota
            $table->longText('production_batch_data');
            $table->longText('produced_items_data');
            $table->string('reason');
            $table->text('attachment')->nullable();
            $table->tinyInteger('status')->default(0); // 0 = deleted, 1 = activated
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->foreign('production_order_id')->references('id')->on('production_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_batches');
    }
};
