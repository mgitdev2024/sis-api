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
        Schema::create('employment_salary_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->string('notice_personnel_action');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('personal_information_id')->references('id')->on('personal_informations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_salary_adjustments');
    }
};
