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
        Schema::create('item_dispositions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->unsignedBigInteger('production_batch_id');
            $table->tinyInteger('type'); //  0 = For Investigation , 1 = For Sampling,
            $table->string('produced_items');
            $table->string('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->tinyInteger('status')->default(1); //  0 = closed , 1 = open,
            $table->timestamps();

            $table->foreign('production_batch_id')->references('id')->on('production_batch');
            $table->foreign('created_by_id')->references('id')->on('credentials');
            $table->foreign('updated_by_id')->references('id')->on('credentials');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_dispositions');
    }
};
