<?php

use App\Helpers\SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wms_warehouse_for_put_away_v2', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_put_away_key');
            $table->string('warehouse_receiving_reference_number');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('temporary_storage_id')->nullable();

            $table->longText('production_items')->nullable();
            $table->unsignedBigInteger('sub_location_id')->nullable();
            $table->integer('layer_level')->nullable();

            SchemaHelper::addCommonColumns($table);
            $table->foreign('sub_location_id')->references('id')->on('wms_storage_sub_locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_for_put_away_v2');
    }
};
