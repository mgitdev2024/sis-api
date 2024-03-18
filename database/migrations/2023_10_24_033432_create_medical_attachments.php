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
        Schema::create('medical_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->string('attachment')->nullable();
            $table->string('description')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            // Define the foreign key constraint
            $table->foreign('personal_information_id')->references('id')->on('personal_informations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_attachments');
    }
};
