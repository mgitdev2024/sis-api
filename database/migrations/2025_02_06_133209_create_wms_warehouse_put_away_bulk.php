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
        Schema::create('wms_warehouse_put_away_bulk', function (Blueprint $table) {
            $table->id();
            $table->longText('temporary_storages');
            $table->unsignedBigInteger('sub_location_id');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('sub_location_id')->references('id')->on('wms_storage_sub_locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wms_warehouse_put_away_bulk');
    }
};
