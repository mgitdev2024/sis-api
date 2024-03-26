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
        Schema::create('produced_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_batch_id');
            $table->text('produced_items'); // JSON
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('credentials');
            $table->foreign('updated_by_id')->references('id')->on('credentials');
            $table->foreign('production_batch_id')->references('id')->on('production_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produced_items');
    }
};
