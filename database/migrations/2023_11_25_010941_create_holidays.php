<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->date('date');
            $table->tinyInteger('is_local')->default(1);
            $table->tinyInteger('is_special')->default(0);
            $table->timestamps();
            $table->foreign('created_by_id')->references('id')->on('personal_informations')->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
