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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('cover')->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('from');
            $table->string('to');
            $table->string('file')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_allow_comment')->default(0);
            $table->tinyInteger('type')->default(1);
            $table->timestamps();
            $table->foreign('created_by_id')->references('id')->on('personal_informations')->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
