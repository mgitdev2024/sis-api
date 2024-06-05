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
        Schema::create('access_module_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scm_system_id');
            $table->string('name');
            $table->string('code');
            $table->string('description')->nullable();
            $table->text('is_enabled')->nullable();
            $table->text('allow_view')->nullable();
            $table->text('allow_create')->nullable();
            $table->text('allow_update')->nullable();
            $table->text('allow_delete')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->foreign('scm_system_id')->references('id')->on('scm_systems');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_module_permissions');
    }
};
