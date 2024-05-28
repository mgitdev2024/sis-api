<?php

use App\Helpers\SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        #region Item Master Data Settings
        Schema::create('item_delivery_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('description');
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            SchemaHelper::addCommonColumns($table);
            
        });

        Schema::create('item_classifications', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description');
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('item_conversions', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('item_movements', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description');
            SchemaHelper::addCommonColumns($table);
            
        });

        Schema::create('item_stock_types', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('item_uoms', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('item_variant_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('short_name');
            $table->string('name');
            SchemaHelper::addCommonColumns($table);
           
        });

        Schema::create('item_variant_type_multipliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_variant_type_id');
            $table->integer('multiplier');
            SchemaHelper::addCommonColumns($table);
            $table->foreign('item_variant_type_id')->references('id')->on('item_variant_types');
        });

        #endregion

        #region Storage Master Data

        Schema::create('storage_types', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description');
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('storage_facility_plants', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description');
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('storage_warehouses', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description');
            $table->unsignedBigInteger('facility_id');
            SchemaHelper::addCommonColumns($table);
            $table->foreign('facility_id')->references('id')->on('storage_facility_plants');
        });

        Schema::create('storage_zones', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description');
            $table->unsignedBigInteger('facility_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('storage_type_id');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('facility_id')->references('id')->on('storage_facility_plants');
            $table->foreign('warehouse_id')->references('id')->on('storage_warehouses');
            $table->foreign('storage_type_id')->references('id')->on('storage_types');
        });

        Schema::create('storage_sub_locations', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->integer('qty');
            $table->unsignedBigInteger('facility_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('zone_id');
            SchemaHelper::addCommonColumns($table);
           
            $table->foreign('facility_id')->references('id')->on('storage_facility_plants');
            $table->foreign('warehouse_id')->references('id')->on('storage_warehouses');
            $table->foreign('zone_id')->references('id')->on('storage_zones');
        });

        Schema::create('storage_sub_location_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->integer('number');
            $table->integer('has_layer');
            $table->unsignedBigInteger('sub_location_id');
            SchemaHelper::addCommonColumns($table);
            
            $table->foreign('sub_location_id')->references('id')->on('storage_sub_locations');
        });

        Schema::create('storage_sub_location_category_layers', function (Blueprint $table) {
            $table->id();
            $table->integer('min');
            $table->integer('max');
            $table->unsignedBigInteger('sub_location_category_id');
            SchemaHelper::addCommonColumns($table);
        
            // $table->foreign('sub_location_category_id')->references('id')->on('storage_sub_location_categories');
        });

        Schema::create('storage_moving_storages', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->unsignedBigInteger('facility_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('zone_id');
            $table->unsignedBigInteger('sub_location_category_id');
            SchemaHelper::addCommonColumns($table);
            $table->foreign('facility_id')->references('id')->on('storage_facility_plants');
            $table->foreign('warehouse_id')->references('id')->on('storage_warehouses');
            $table->foreign('zone_id')->references('id')->on('storage_zones');
            $table->foreign('sub_location_category_id')->references('id')->on('storage_sub_location_categories');
        });

        #endregion

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('item_classifications');
        Schema::dropIfExists('item_conversions');
        Schema::dropIfExists('item_movements');
        Schema::dropIfExists('item_stock_types');
        Schema::dropIfExists('item_uoms');
        Schema::dropIfExists('item_variant_types');
        Schema::dropIfExists('item_variant_type_multipliers');
        Schema::dropIfExists('item_delivery_types');


        Schema::dropIfExists('storage_types');
        Schema::dropIfExists('storage_facility_plants');
        Schema::dropIfExists('storage_warehouses');
        Schema::dropIfExists('storage_zones');
        Schema::dropIfExists('storage_sub_locations');
        Schema::dropIfExists('storage_sub_location_categories');
        Schema::dropIfExists('storage_sub_location_category_layers');
        Schema::dropIfExists('storage_moving_storages');

        
  
    }
};
