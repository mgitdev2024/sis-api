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
        #region Warehouse Stock Transfer List
        Schema::create('wms_stock_transfer_lists', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->integer('requested_item_count');
            $table->text('reason');
            SchemaHelper::addCommonColumns($table); // 0 = Cancelled, 1 = For Transfer, 2 = In Process, 3 = Transferred
        });
        #endregion

        #region Warehouse Stock Transfer List
        Schema::create('wms_stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_list_id');
            $table->string('item_code');
            $table->longText('selected_items')->nullable();
            $table->integer('initial_stock');
            $table->integer('transfer_quantity');
            $table->unsignedBigInteger('zone_id');
            $table->unsignedBigInteger('sub_location_id');
            $table->unsignedBigInteger('layer')->nullable();
            SchemaHelper::addCommonColumns($table, 0); // 0 = For Transfer, 1 = In Process, 2 = Transferred
        });
        #endregion

        #region Warehouse Stock Transfer List
        Schema::create('wms_stock_transfer_cancelled', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_list_id');
            $table->text('reason');
            $table->text('attachment')->nullable();
            SchemaHelper::addCommonColumns($table);
        });
        #endregion
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
