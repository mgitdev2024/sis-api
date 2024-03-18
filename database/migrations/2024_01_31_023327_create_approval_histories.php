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
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_ticket_id');
            $table->integer('level');
            $table->unsignedBigInteger('approval_configuration_id');
            $table->tinyInteger('is_approved')->default(0);
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->foreign('approval_ticket_id')->references('id')->on('approval_tickets');
            $table->foreign('approval_configuration_id')->references('id')->on('approval_configurations');
            $table->foreign('created_by_id')->references('id')->on('personal_informations');
            $table->foreign('updated_by_id')->references('id')->on('personal_informations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
    }
};
