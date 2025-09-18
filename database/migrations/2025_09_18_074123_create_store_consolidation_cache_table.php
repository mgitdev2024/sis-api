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
        Schema::create('store_consolidation_cache', function (Blueprint $table) {
            $table->id();
            $table->string('created_by_id');
            $table->string('created_by_name');
            $table->longText('consolidated_data');
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_consolidation_cache');
    }
};
