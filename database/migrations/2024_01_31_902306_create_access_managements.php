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
        Schema::create('access_managements', function (Blueprint $table) {
            $table->id();
            $table->string('access_code');
            $table->string('preset_name');
            $table->text('access_points');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1); // active 1
            $table->unsignedBigInteger('approval_ticket_id');
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('personal_informations');
            $table->foreign('updated_by_id')->references('id')->on('personal_informations');
            $table->foreign('approval_ticket_id')->references('id')->on('approval_tickets');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_managements');
    }
};
