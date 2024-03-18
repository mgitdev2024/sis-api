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
        Schema::create('approval_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_workflow_id');
            $table->integer('level');
            $table->unsignedBigInteger('approval_level_id');
            $table->unsignedBigInteger('approver_id');
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->foreign('approval_workflow_id')->references('id')->on('approval_workflows');
            $table->foreign('approver_id')->references('id')->on('personal_informations');
            $table->foreign('approval_level_id')->references('id')->on('approval_levels');
            $table->foreign('created_by_id')->references('id')->on('personal_informations');
            $table->foreign('updated_by_id')->references('id')->on('personal_informations');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_configurations');
    }
};
