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
        Schema::create('memoranda', function (Blueprint $table) {
            $table->id();
            // Department ID
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('reference_number');
            $table->string('subject');
            $table->text('description');
            $table->string('from');
            $table->string('to');
            $table->date('effective_date');
            $table->string('file')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_pinned')->default(0);
            $table->timestamps();
            $table->foreign('created_by_id')->references('id')->on('personal_informations')->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memoranda');
    }
};
