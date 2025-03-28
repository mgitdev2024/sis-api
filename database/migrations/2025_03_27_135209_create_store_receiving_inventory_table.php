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
            $table->string('warehouse_code'); // BK-BREADS
            $table->string('store_code'); // C001
            $table->string('store_name');
            $table->date('delivery_date');
            $table->string('delivery_type');
            $table->date('order_date');
            $table->string('item_code');
            $table->integer('order_quantity');
            $table->integer('received_quantity');
            $table->longText('received_items'); // JSON Data of each item scanned
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
