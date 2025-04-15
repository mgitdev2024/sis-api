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
            $table->unsignedBigInteger('consolidated_order_id'); // Check MGIOS for consolidated_order_table
            $table->string('reference_number');
            $table->date('delivery_date');
            $table->string('delivery_type');
            $table->string('warehouse_code'); // BK-BREADS
            $table->string('warehouse_name'); // BK-BREADS
            $table->string('created_by_name');
            SchemaHelper::addCommonColumns($table, 0);
        });

        Schema::create('store_receiving_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_receiving_inventory_id'); // FK to store_receiving_inventory
            $table->string('store_code'); // C001
            $table->string('store_name');
            $table->integer('store_sub_unit_id');
            $table->string('store_sub_unit_short_name');
            $table->string('store_sub_unit_long_name');
            $table->date('delivery_date');
            $table->string('delivery_type');
            $table->date('order_date');
            $table->string('item_code');
            $table->string('item_description');
            $table->integer('order_quantity');
            $table->integer('allocated_quantity');
            $table->integer('received_quantity');
            $table->longText('received_items')->nullable(); // JSON Data of each item scanned
            $table->boolean('is_special')->default(false); // 0 = Regular, 1 = Special
            $table->string('order_session_id');
            $table->boolean('is_wrong_drop')->default(false); // 0 = No, 1 = Yes
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
    }
};
