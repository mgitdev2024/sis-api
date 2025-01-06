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
        Schema::create('wms_generate_picklists', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->unsignedBigInteger('allocation_order_id');
            $table->string('consolidation_reference_number');

            SchemaHelper::addCommonColumns($table, 0); // 0 = pending, 1 = complete
            $table->foreign('allocation_order_id')->references('id')->on('wms_allocation_orders');
        });

        Schema::create('wms_generate_picklist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generate_picklist_id');
            $table->string('store_id')->nullable();
            $table->string('store_name')->nullable();
            $table->longText('picklist_items'); // {"item_id"=>1,"allocated_qty"=>22,"scanned_qty"=>22} "checked_qty"=>22 item__data:{bid:1,sticker_no:1}

            SchemaHelper::addCommonColumns($table, 0); // 0 = pending, 1 = complete
            $table->foreign('generate_picklist_id')->references('id')->on('wms_generate_picklists');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wms_generate_picklists');
        Schema::dropIfExists('wms_generate_picklist_items');

    }
};
