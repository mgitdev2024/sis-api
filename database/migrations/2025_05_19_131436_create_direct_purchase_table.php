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
            // $table->string('direct_reference_number'); // MG-0800-4382-2331 PO Number
            // $table->tinyInteger('type'); // 0 = DR, 1 = PO
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            $table->string('supplier_code')->nullable(); // ABMARAC Corp
            $table->string('supplier_name')->nullable(); // ABMARAC Corp
            $table->date('direct_purchase_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->text('attachment')->nullable();
            $table->text('remarks')->nullable();
            SchemaHelper::addCommonColumns($table, 0); // 0 = Draft, 1 = Posted / Complete, 2 = Cancelled
        });

        Schema::create('direct_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('direct_purchase_id'); // FK to direct_purchases
            $table->string('item_code')->nullable(); // CR 12
            $table->string('item_description')->nullable(); // Cheeseroll Box of 12
            $table->string('item_category_code')->nullable();
            $table->string('uom')->nullable();
            $table->integer('quantity');
            $table->text('remarks')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('direct_purchase_id')->references('id')->on('direct_purchases');
        });
        //* w/ SAP Structure
        Schema::create('sap_direct_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('definition_id'); //* Static value
            $table->string('bpa_response_id')->nullable();
            $table->string('purchase_requisition_type')->nullable();
            $table->text('attachment')->nullable();
            $table->text('remarks')->nullable();
            SchemaHelper::addCommonColumns($table, 1);

        });
        Schema::create('sap_direct_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('direct_purchase_id'); //* FK to sap_direct_purchase
            $table->unsignedBigInteger('purchase_requisition_item'); //* by 10's incrementation
            $table->string('material')->nullable(); //* Item Code
            $table->string('material_group')->nullable(); //* Item Category Code
            $table->string('plant')->nullable(); //* Store Code
            $table->string('company_code')->nullable(); //* Store Company Code
            $table->string('base_unit_iso_code')->nullable(); //uom
            $table->string('purchasing_organization')->nullable();
            $table->string('purchasing_group')->nullable();
            $table->unsignedBigInteger('requested_quantity')->nullable();
            $table->unsignedBigInteger('purchase_requisition_price')->nullable(); //* Price
            $table->string('purchase_requisition_item_currency')->nullable();//* Currency
            $table->date('delivery_date')->nullable(); //* Expected Delivery Date
            $table->string('storage_location')->nullable();
            $table->text('purchase_requisition_item_text')->nullable(); //* Remarks
            SchemaHelper::addCommonColumns($table, 0);
        });

        // Schema::create('direct_purchase_handled_items', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('direct_purchase_item_id'); // FK to direct_purchase_items
        //     $table->string('delivery_receipt_number')->nullable(); // 9843
        //     $table->integer('quantity');
        //     $table->string('storage'); // default
        //     $table->string('remarks')->nullable();
        //     $table->tinyInteger('type'); // 0 = rejected, 1 = received
        //     $table->date('expiration_date')->nullable();
        //     $table->date('received_date');

        //     SchemaHelper::addCommonColumns($table, 0); // 0 = pending, 1 = posted, 2 = deleted

        //     $table->foreign('direct_purchase_item_id')->references('id')->on('direct_purchase_items');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_purchases');
        Schema::dropIfExists('direct_purchase_items');
        // Schema::dropIfExists('direct_purchase_handled_items');
    }
};