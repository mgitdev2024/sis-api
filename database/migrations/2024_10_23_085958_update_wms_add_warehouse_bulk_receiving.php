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
        Schema::create('wms_warehouse_receiving_bulk', function (Blueprint $table) {
            $table->id();
            $table->integer('bulk_transaction_number');
            $table->string('reference_number');
            $table->longText('production_items')->nullable();
            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wms_warehouse_receiving_bulk');
    }
};
