<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('print_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_batch_id');
            $table->string('produce_items');
            $table->string('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->tinyInteger('is_reprint')->default(0);
            $table->timestamps();
            $table->foreign('production_batch_id')->references('id')->on('production_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_history');
    }
};
