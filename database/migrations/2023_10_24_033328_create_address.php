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
        Schema::create('address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_information_id');
            $table->string('full_address')->nullable();
            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('municipality')->nullable();
            $table->string('city')->nullable();
            $table->string('barangay')->nullable();
            $table->string('zip_code')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('type')->default(0);
            $table->timestamps();
            // Define the foreign key constraint
            $table->foreign('personal_information_id')->references('id')->on('personal_informations')->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('address');
    }
};
