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
        Schema::create('module_functions', function (Blueprint $table) {
            $table->id();
            $table->string('function_code');
            $table->unsignedBigInteger('sub_module_id');
            $table->unsignedBigInteger('module_permission_id');
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('personal_informations');
            $table->foreign('updated_by_id')->references('id')->on('personal_informations');
            $table->foreign('module_permission_id')->references('id')->on('module_permissions');
            $table->foreign('sub_module_id')->references('id')->on('sub_modules');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_access');
    }
};
