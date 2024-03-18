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
        Schema::create('educational_background', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->string('school_name');
            $table->string('level');
            $table->string('degrees_achieved')->nullable();
            $table->string('year_graduated')->nullable();
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
        Schema::dropIfExists('educational_background');
    }
};
