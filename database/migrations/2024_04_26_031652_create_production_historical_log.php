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
        Schema::create('production_historical_logs', function (Blueprint $table) {
            $table->id();
            // $table->string('transaction_no')->nullable();
            $table->string('entity_model');
            $table->integer('entity_id');
            $table->integer('item_key')->nullable();
            $table->text('data');
            $table->tinyInteger('action'); // 0 = Create, 1 = Update, 2 = Delete
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_historical_log');
    }
};
