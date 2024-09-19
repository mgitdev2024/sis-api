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
        Schema::create('admin_system_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('entity_id');
            $table->text('entity_model');
            $table->longText('data');
            $table->tinyInteger('action')->default(0); // 0 = Create, 1 = Update, 2 = Delete
            SchemaHelper::addCommonColumns($table, 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_system_logs');
    }
};
