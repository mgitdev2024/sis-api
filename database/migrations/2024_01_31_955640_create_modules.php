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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('approval_workflow_id')->nullable();

            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('internal_system_id');
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('personal_informations');
            $table->foreign('updated_by_id')->references('id')->on('personal_informations');
            $table->foreign('internal_system_id')->references('id')->on('internal_systems');
            $table->foreign('approval_workflow_id')->references('id')->on('approval_workflows')->onDelete('restrict');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
