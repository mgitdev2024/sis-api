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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('category_code');
            $table->string('category_name');
            $table->tinyInteger('status')->default(1);
            $table->foreign('created_by_id')->references('id')->on('credentials');
            $table->foreign('updated_by_id')->references('id')->on('credentials');
            $table->timestamps();
        });
        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->foreignId('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->string('sub_category_code');
            $table->string('sub_category_name');
            $table->tinyInteger('status')->default(1);
            $table->foreign('created_by_id')->references('id')->on('credentials');
            $table->foreign('updated_by_id')->references('id')->on('credentials');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('sub_categories');
    }
};
