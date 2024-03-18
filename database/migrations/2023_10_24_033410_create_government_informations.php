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
        Schema::create('government_informations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->string('sss_number')->nullable();
            $table->string('sss_id_pic')->nullable();
            $table->string('philhealth_number')->nullable();
            $table->string('philhealth_id_pic')->nullable();
            $table->string('pagibig_number')->nullable();
            $table->string('pagibig_id_pic')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('tin_id_pic')->nullable();
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
        Schema::dropIfExists('government_informations');
    }
};
