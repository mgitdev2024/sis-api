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
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('job_code')->unique();
            $table->string('job_title');
            $table->text('job_description')->nullable();
            $table->integer('slot');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('personal_informations');
            $table->foreign('updated_by_id')->references('id')->on('personal_informations');
            $table->foreign('section_id')->references('id')->on('sections');
            $table->foreign('division_id')->references('id')->on('divisions');
            $table->foreign('department_id')->references('id')->on('departments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};
