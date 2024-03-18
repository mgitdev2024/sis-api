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
        Schema::create('employment_information', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->string('id_picture')->nullable();
            $table->string('company_id');
            $table->string('branch_id');
            $table->string('department_id');
            $table->string('section_id');
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('workforce_division_id');
            $table->string('employment_classification');
            $table->date('date_hired');
            $table->tinyInteger('onboarding_status');
            $table->timestamps();
            $table->foreign('personal_information_id')->references('id')->on('personal_informations')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('organizational_structure')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employement_informations');
    }
};
