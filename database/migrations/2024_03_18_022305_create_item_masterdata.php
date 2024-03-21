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
        Schema::create('item_masterdata', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->index();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('item_classification_id');
            $table->unsignedBigInteger('item_variant_type_id');
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('credentials');
            $table->foreign('updated_by_id')->references('id')->on('credentials');
            $table->foreign('item_classification_id')->references('id')->on('item_classifications')->onDelete('restrict');
            $table->foreign('item_variant_type_id')->references('id')->on('item_variant_types')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_masterdata');
    }
};
