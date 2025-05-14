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
        Schema::create('store_receiving_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('consolidated_order_id')->nullable(); // Check MGIOS for consolidated_order_table
            $table->string('reference_number');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_type')->nullable();
            $table->string('warehouse_code')->nullable(); // BK-BREADS
            $table->string('warehouse_name')->nullable(); // BK-BREADS
            $table->string('created_by_name');
            SchemaHelper::addCommonColumns($table, 0);
        });

        Schema::create('store_receiving_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_receiving_inventory_id'); // FK to store_receiving_inventory
            $table->string('store_code'); // C001
            $table->string('store_name');
            // $table->integer('store_sub_unit_id');
            $table->string('store_sub_unit_short_name');
            $table->string('store_sub_unit_long_name');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_type')->nullable();
            $table->date('order_date')->nullable();
            $table->string('item_code');
            $table->string('item_description');
            $table->string('item_category_name');
            $table->integer('order_quantity')->nullable();
            $table->integer('allocated_quantity')->nullable();
            $table->integer('received_quantity')->nullable();
            $table->longText('received_items')->nullable(); // JSON Data of each item scanned
            $table->tinyInteger('receive_type')->nullable(); // 0 = scan 1 = manual
            $table->tinyInteger('order_type')->default(0); // 0 = Regular, 1 = Special, 2 = Fan out
            $table->tinyInteger('fan_out_category')->nullable(); // 0 = Fresh, 1 = Pulled out
            $table->string('reference_number')->nullable();
            $table->boolean('is_wrong_drop')->default(false); // 0 = No, 1 = Yes
            $table->tinyInteger('type'); // 0 = order 1 = receiving, 2 = store transfer, 3 = pull out
            $table->tinyInteger('is_received')->default(0); // 0 = No, 1 = Yes

            $table->string('created_by_name');
            SchemaHelper::addCommonColumns($table, 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_receiving_inventory');
        Schema::dropIfExists('store_receiving_inventory_items');
    }
};
