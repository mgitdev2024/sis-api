<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\SchemaHelper;
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('archived_production_logs', function (Blueprint $table) {
            $table->id();
            // $table->string('transaction_no')->nullable();
            $table->string('entity_model');
            $table->integer('entity_id');
            $table->integer('item_key')->nullable();
            $table->longText('data');
            $table->tinyInteger('action'); // 0 = Create, 1 = Update, 2 = Delete
            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_production_logs');
    }
};
