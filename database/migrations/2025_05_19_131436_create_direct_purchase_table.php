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
        Schema::create('direct_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number'); // DP-0000001
            $table->string('direct_reference_number'); // MG-0800-4382-2331 PO Number
            $table->tinyInteger('type'); // 0 = DR, 1 = PO
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            $table->string('supplier_code')->nullable(); // ABMARAC Corp
            $table->string('supplier_name'); // ABMARAC Corp
            $table->date('direct_purchase_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            SchemaHelper::addCommonColumns($table, 0); // 0 = Pending, 1 = Closed / Complete
        });

        Schema::create('direct_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('direct_purchase_id'); // FK to direct_purchases
            $table->string('item_code'); // CR 12
            $table->string('item_description'); // Cheeseroll Box of 12
            $table->string('item_category_name');
            $table->integer('total_received_quantity')->default(0);
            $table->integer('requested_quantity');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('direct_purchase_id')->references('id')->on('direct_purchases');
        });

        Schema::create('direct_purchase_handled_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('direct_purchase_item_id'); // FK to direct_purchase_items
            $table->string('delivery_receipt_number')->nullable(); // 9843
            $table->integer('quantity');
            $table->string('storage'); // default
            $table->string('remarks')->nullable();
            $table->tinyInteger('type'); // 0 = rejected, 1 = received
            $table->date('expiration_date')->nullable();
            $table->date('received_date');

            SchemaHelper::addCommonColumns($table, 0); // 0 = pending, 1 = posted, 2 = deleted

            $table->foreign('direct_purchase_item_id')->references('id')->on('direct_purchase_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_purchases');
        Schema::dropIfExists('direct_purchase_items');
        Schema::dropIfExists('direct_purchase_handled_items');
    }
};
