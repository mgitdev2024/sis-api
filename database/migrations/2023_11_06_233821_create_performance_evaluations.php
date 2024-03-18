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
        Schema::create('performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->string('evaluation_third')->nullable();
            $table->string('evaluation_fifth')->nullable();
            $table->text('appraisal')->nullable();
            $table->text('pip')->nullable();
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
        Schema::dropIfExists('performance_evaluations');
    }
};
