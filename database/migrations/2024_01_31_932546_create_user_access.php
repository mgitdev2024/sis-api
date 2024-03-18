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
        Schema::create('user_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credential_id');
            $table->string('customized_user_access')->nullable();
            $table->unsignedBigInteger('access_management_id');
            $table->unsignedBigInteger('is_customized')->default(0);
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->foreign('credential_id')->references('id')->on('credentials');
            $table->foreign('access_management_id')->references('id')->on('access_managements');
            $table->foreign('created_by_id')->references('id')->on('personal_informations');
            $table->foreign('updated_by_id')->references('id')->on('personal_informations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_access');
    }
};
