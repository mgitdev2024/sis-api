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
        Schema::create('wms_stock_dispatch', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->unsignedBigInteger('generate_picklist_id')->nullable();
            SchemaHelper::addCommonColumns($table, 0); // 0 = pending, 1 = complete
            $table->timestamps();
        });

        Schema::create('wms_stock_dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_dispatch_id');
            $table->string('store_id')->nullable();
            $table->string('store_name')->nullable();
            $table->longText('dispatch_items');
            SchemaHelper::addCommonColumns($table, 0); // 0 = pending, 1 = complete
            $table->timestamps();

            $table->foreign('stock_dispatch_id')->references('id')->on('wms_stock_dispatch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wms_stock_dispatch');
        Schema::dropIfExists('wms_stock_dispatch_items');
    }
};
