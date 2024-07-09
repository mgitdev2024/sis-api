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
        Schema::create('mos_production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->date('production_date');
            SchemaHelper::addCommonColumns($table, 0); // 0 = Pending, 1 = Complete
        });

        Schema::create('mos_production_otas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->string('item_code');
            $table->integer('requested_quantity');
            $table->float('buffer_level');
            $table->float('buffer_quantity');
            $table->float('plotted_quantity');
            $table->integer('actual_quantity')->default(0);
            $table->integer('actual_secondary_quantity')->default(0);
            $table->date('expected_ambient_exp_date')->nullable();
            $table->date('expected_chilled_exp_date')->nullable();
            $table->date('expected_frozen_exp_date')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('production_order_id')->references('id')->on('mos_production_orders');
            $table->foreign('item_code')->references('item_code')->on('wms_item_masterdata');
        });

        Schema::create('mos_production_otbs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->string('delivery_type');
            $table->string('item_code');
            $table->integer('requested_quantity');
            $table->float('buffer_level');
            $table->float('buffer_quantity');
            $table->float('plotted_quantity');
            $table->integer('actual_quantity')->default(0);
            $table->integer('actual_secondary_quantity')->default(0);
            $table->date('expected_ambient_exp_date')->nullable();
            $table->date('expected_chilled_exp_date')->nullable();
            $table->date('expected_frozen_exp_date')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('production_order_id')->references('id')->on('mos_production_orders');
            $table->foreign('item_code')->references('item_code')->on('wms_item_masterdata');
            $table->foreign('delivery_type')->references('code')->on('wms_item_delivery_types');

        });

        Schema::create('mos_production_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_otb_id')->nullable();
            $table->unsignedBigInteger('production_ota_id')->nullable();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('production_item_id')->nullable();
            $table->string('batch_code');
            $table->integer('batch_number');
            $table->tinyInteger('batch_type');
            $table->string('quantity');
            $table->integer('actual_quantity')->default(0);
            $table->integer('actual_secondary_quantity')->default(0);
            $table->date('ambient_exp_date')->nullable();
            $table->date('chilled_exp_date')->nullable();
            $table->date('frozen_exp_date')->nullable();
            $table->tinyInteger('has_endorsement_from_qa')->default(0); // 0 = No Endorsement, 1 = Has Endorsement
            $table->tinyInteger('is_printed')->default(0); // 0 = Not Printed, 1 = Printed
            SchemaHelper::addCommonColumns($table, 0);

            $table->foreign('production_otb_id')->references('id')->on('mos_production_otbs');
            $table->foreign('production_ota_id')->references('id')->on('mos_production_otas');
        });

        Schema::create('mos_production_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_batch_id');
            $table->longText('produced_items'); // JSON
            $table->tinyInteger('production_type'); // 0 = otb, 1 = ota
            SchemaHelper::addCommonColumns($table);

            $table->foreign('production_batch_id')->references('id')->on('mos_production_batches');
        });

        Schema::create('mos_production_print_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_batch_id');
            $table->longText('produced_items');
            $table->string('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->tinyInteger('is_reprint')->default(0);
            $table->tinyInteger('item_disposition_id')->nullable();
            SchemaHelper::addCommonColumns($table);

        });

        Schema::create('mos_production_archived_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->integer('batch_number');
            $table->tinyInteger('production_type'); // 0 = otb, 1 = ota
            $table->longText('production_batch_data');
            $table->longText('produced_items_data');
            $table->string('reason');
            $table->text('attachment')->nullable();
            SchemaHelper::addCommonColumns($table, 0); // 0 = deleted, 1 = activated
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreign('production_order_id')->references('id')->on('mos_production_orders');

        });

        Schema::create('mos_production_logs', function (Blueprint $table) {
            $table->id();
            // $table->string('transaction_no')->nullable();
            $table->string('entity_model');
            $table->integer('entity_id');
            $table->integer('item_key')->nullable();
            $table->longText('data');
            $table->tinyInteger('action'); // 0 = Create, 1 = Update, 2 = Delete
            SchemaHelper::addCommonColumns($table);
        });


        Schema::create('wms_warehouse_receiving', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('production_batch_id');
            $table->integer('batch_number');
            $table->string('item_code');
            $table->string('sku_type');
            $table->longText('produced_items');
            $table->integer('quantity');
            $table->integer('received_quantity')->default(0);
            $table->integer('substandard_quantity')->default(0);
            $table->longText('substandard_data')->nullable();
            $table->longText('discrepancy_data')->nullable();

            SchemaHelper::addCommonColumns($table, 0); // 0 = pending, 1 = complete

            $table->foreign('production_order_id')->references('id')->on('mos_production_orders');
            $table->foreign('production_batch_id')->references('id')->on('mos_production_batches');
        });

        Schema::create('wms_warehouse_put_away', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_receiving_reference_number');
            $table->string('reference_number'); // e.g 8000001-1
            $table->string('item_code');
            $table->longText('production_items');
            $table->text('received_quantity'); // e.g Box: 4 , pieces: 300
            $table->text('transferred_quantity')->nullable(); // e.g Box: 4 , pieces: 300
            $table->text('substandard_quantity')->nullable(); // e.g Box: 4 , pieces: 300
            $table->text('remaining_quantity'); // e.g Box: 4 , pieces: 300
            $table->longText('discrepancy_data')->nullable();
            SchemaHelper::addCommonColumns($table, 0); // 0 = pending, 1 = complete
        });

        Schema::create('wms_warehouse_for_put_away', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_receiving_reference_number');
            $table->unsignedBigInteger('warehouse_put_away_id');
            $table->string('item_code');
            $table->longText('production_items');
            $table->unsignedBigInteger('sub_location_id')->nullable();
            $table->integer('layer_level')->nullable();

            SchemaHelper::addCommonColumns($table);

            $table->foreign('warehouse_put_away_id')->references('id')->on('wms_warehouse_put_away');
            $table->foreign('sub_location_id')->references('id')->on('wms_storage_sub_locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mos_production_orders');
        Schema::dropIfExists('mos_production_otas');
        Schema::dropIfExists('mos_production_otbs');
        Schema::dropIfExists('mos_production_batches');
        Schema::dropIfExists('mos_production_items');
        Schema::dropIfExists('mos_production_logs');
        Schema::dropIfExists('mos_production_print_histories');
        Schema::dropIfExists('mos_production_archived_batches');
        Schema::dropIfExists('wms_warehouse_receiving');
        Schema::dropIfExists('wms_warehouse_put_away');
        Schema::dropIfExists('wms_warehouse_for_put_away');

    }

};
