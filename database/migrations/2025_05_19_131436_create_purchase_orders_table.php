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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number'); // MG-0800-4382-2331 PO Number
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name'); // FOH BOH
            $table->string('supplier_name'); // ABMARAC Corp
            $table->date('purchase_order_date');
            $table->date('expected_delivery_date');
            SchemaHelper::addCommonColumns($table, 0); // 0 = Pending, 1 = Closed / Complete
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id'); // FK to purchase_orders
            $table->string('item_code'); // CR 12
            $table->string('item_description'); // Cheeseroll Box of 12
            $table->string('item_category_name');
            $table->integer('total_quantity_received');
            $table->integer('requested_quantity');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders');
        });

        Schema::create('purchase_order_handled_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_item_id'); // FK to purchase_order_items
            $table->string('delivery_receipt_number'); // 9843
            $table->integer('quantity');
            $table->string('storage'); // default
            $table->string('remarks')->nullable();
            $table->tinyInteger('type'); // 0 = rejected, 1 = received

            SchemaHelper::addCommonColumns($table);

            $table->foreign('purchase_order_items')->references('id')->on('purchase_order_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_order_handled_items');
    }
};
