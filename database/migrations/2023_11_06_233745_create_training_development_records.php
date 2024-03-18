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
        Schema::create('training_development_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->string('training_attended')->nullable();
            $table->string('certificate')->nullable();
            $table->string('bond_contract')->nullable();
            $table->string('obligatory_training')->nullable();
            $table->string('nhe_orientation')->nullable();
            $table->date('duration_required')->nullable();
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
        Schema::dropIfExists('training_development_records');
    }
};
