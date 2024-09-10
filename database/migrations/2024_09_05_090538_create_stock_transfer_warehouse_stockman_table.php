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
        Schema::create('wms_stock_request_for_transfer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_list_id');
            $table->unsignedBigInteger('stock_transfer_item_id');
            // $table->string('item_code')->nullable();
            $table->longText('scanned_items')->nullable();
            $table->unsignedBigInteger('sub_location_id');
            $table->integer('layer_level');
            SchemaHelper::addCommonColumns($table); // 1 = active, 2 = inactive
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_warehouse_stockman');
    }
};
