<?php

use App\Helpers\SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        #region Item Master Data Settings
        Schema::create('wms_item_delivery_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index();
            $table->string('name');
            $table->string('description')->nullable();
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('wms_item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            SchemaHelper::addCommonColumns($table);

        });

        Schema::create('wms_item_classifications', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description')->nullable();
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('wms_item_conversions', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('wms_item_movements', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description')->nullable();
            SchemaHelper::addCommonColumns($table);

        });

        Schema::create('wms_item_stock_types', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('wms_item_uoms', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('wms_item_variant_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('short_name');
            $table->string('name');
            SchemaHelper::addCommonColumns($table);

        });

        Schema::create('wms_item_variant_type_multipliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_variant_type_id');
            $table->integer('multiplier');
            SchemaHelper::addCommonColumns($table);
            $table->foreign('item_variant_type_id')->references('id')->on('wms_item_variant_types');
        });


        #endregion

        #region Storage Master Data

        Schema::create('wms_storage_types', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description')->nullable();
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('wms_storage_facility_plants', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description')->nullable();
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('wms_storage_warehouses', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('facility_id');
            SchemaHelper::addCommonColumns($table);
            $table->foreign('facility_id')->references('id')->on('wms_storage_facility_plants');
        });

        Schema::create('wms_storage_zones', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('facility_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('storage_type_id');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('facility_id')->references('id')->on('wms_storage_facility_plants');
            $table->foreign('warehouse_id')->references('id')->on('wms_storage_warehouses');
            $table->foreign('storage_type_id')->references('id')->on('wms_storage_types');
        });

        Schema::create('wms_storage_sub_location_type', function (Blueprint $table) {
            $table->id();
            SchemaHelper::addCodeShortLongNameColumns($table);
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('wms_storage_sub_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->integer('number');
            $table->tinyInteger('has_layer')->default(0);
            $table->tinyInteger('is_permanent')->default(0);
            $table->longText('layers')->nullable();
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('sub_location_type_id');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('sub_location_type_id')->references('id')->on('wms_storage_sub_location_type');
            $table->foreign('facility_id')->references('id')->on('wms_storage_facility_plants');
            $table->foreign('warehouse_id')->references('id')->on('wms_storage_warehouses');
            $table->foreign('zone_id')->references('id')->on('wms_storage_zones');
        });
        #endregion

        #region Queued Temporary Storages
        Schema::create('wms_queued_temporary_storages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_location_id');
            $table->integer('layer_level');
            $table->longText('production_items')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('storage_remaining_space')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('sub_location_id')->references('id')->on('wms_storage_sub_locations');
        });
        #endregion

        #region Queued Sub Locations
        Schema::create('wms_queued_sub_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_location_id');
            $table->integer('layer_level');
            $table->longText('production_items')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('storage_remaining_space')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('sub_location_id')->references('id')->on('wms_storage_sub_locations');
        });
        #endregion

        #region Item Master Data
        Schema::create('wms_item_masterdata', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique()->index();
            $table->string('description');
            $table->string('short_name');
            $table->unsignedBigInteger('item_category_id')->nullable();
            $table->unsignedBigInteger('item_classification_id')->nullable();
            $table->unsignedBigInteger('item_variant_type_id')->nullable();
            $table->unsignedBigInteger('parent_item_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->unsignedBigInteger('storage_type_id')->nullable();
            $table->unsignedBigInteger('stock_type_id')->nullable();
            $table->unsignedBigInteger('item_movement_id')->nullable();
            $table->integer('delivery_lead_time')->nullable();
            $table->integer('re_order_level')->nullable();
            $table->integer('stock_rotation_type')->nullable();
            $table->integer('qty_per_pallet')->nullable();
            $table->string('dimension')->nullable();
            $table->integer('is_qa_required')->nullable();
            $table->integer('is_qa_disposal')->nullable();
            $table->string('attachment')->nullable();
            $table->integer('primary_item_packing_size')->nullable();
            $table->unsignedBigInteger('primary_conversion_id')->nullable();
            $table->integer('secondary_item_packing_size')->nullable();
            $table->unsignedBigInteger('secondary_conversion_id')->nullable();
            $table->integer('ambient_shelf_life')->nullable();
            $table->integer('chilled_shelf_life')->nullable();
            $table->integer('frozen_shelf_life')->nullable();
            $table->string('sticker_remarks_code')->nullable();

            $table->unsignedBigInteger('plant_id');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('item_category_id')->references('id')->on('wms_item_categories')->onDelete('restrict');
            $table->foreign('item_classification_id')->references('id')->on('wms_item_classifications')->onDelete('restrict');
            $table->foreign('item_variant_type_id')->references('id')->on('wms_item_variant_types')->onDelete('restrict');
            $table->foreign('plant_id')->references('id')->on('wms_storage_facility_plants')->onDelete('restrict');
            $table->foreign('uom_id')->references('id')->on('wms_item_uoms')->onDelete('restrict');
            $table->foreign('storage_type_id')->references('id')->on('wms_storage_types')->onDelete('restrict');
            $table->foreign('stock_type_id')->references('id')->on('wms_item_stock_types')->onDelete('restrict');
            $table->foreign('item_movement_id')->references('id')->on('wms_item_movements')->onDelete('restrict');
            $table->foreign('primary_conversion_id')->references('id')->on('wms_item_conversions')->onDelete('restrict');
            $table->foreign('secondary_conversion_id')->references('id')->on('wms_item_conversions')->onDelete('restrict');

        });
        #endregion

        #region Stock Inventories
        Schema::create('wms_stock_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('item_code');
            $table->integer('stock_count')->default(0);
            $table->integer('storage_remaining_space')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('item_code')->references('item_code')->on('wms_item_masterdata');
        });
        #endregion

        #region Stock Logs
        Schema::create('wms_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->string('item_code');
            $table->tinyInteger('action'); // 1 = In, 0 = Out;
            $table->integer('quantity');
            $table->unsignedBigInteger('sub_location_id');
            $table->integer('layer_level');
            $table->integer('storage_remaining_space')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('item_code')->references('item_code')->on('wms_item_masterdata');
            $table->foreign('sub_location_id')->references('id')->on('wms_storage_sub_locations');
        });
        #endregion

        #region Warehouse For Receive
        Schema::create('wms_warehouse_for_receive', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->longText('production_items');
            SchemaHelper::addCommonColumns($table);
        });
        #endregion
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wms_item_masterdata');
        Schema::dropIfExists('wms_item_categories');
        Schema::dropIfExists('wms_item_classifications');
        Schema::dropIfExists('wms_item_conversions');
        Schema::dropIfExists('wms_item_movements');
        Schema::dropIfExists('wms_item_stock_types');
        Schema::dropIfExists('wms_item_uoms');
        Schema::dropIfExists('wms_item_variant_types');
        Schema::dropIfExists('wms_item_variant_type_multipliers');
        Schema::dropIfExists('wms_item_delivery_types');


        Schema::dropIfExists('wms_storage_types');
        Schema::dropIfExists('wms_storage_facility_plants');
        Schema::dropIfExists('wms_storage_warehouses');
        Schema::dropIfExists('wms_storage_zones');
        Schema::dropIfExists('wms_storage_sub_location_type');
        Schema::dropIfExists('wms_storage_sub_locations');

        Schema::dropIfExists('wms_stock_logs');
        Schema::dropIfExists('wms_stock_inventories');

        Schema::dropIfExists('wms_queued_sub_locations');
        Schema::dropIfExists('wms_queued_temporary_storages');

        Schema::dropIfExists('wms_warehouse_for_receive');
    }
};
