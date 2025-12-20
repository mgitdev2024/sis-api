<?php
use App\Helpers\SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**.0
     * Run the migrations.
     */
    public function up(): void
    {

        //* w/out SAP Structure
        Schema::create('purchase_request', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number'); //* PR-000001
            $table->unsignedBigInteger('type');  //* 0 = Regular PR, 1 Staggered PR
            $table->string('store_code'); // 17CA
            $table->string('store_sub_unit_short_name')->nullable();
            $table->string('store_company_code')->nullable(); // MGFI FTFI BMII
            $table->string('storage_location')->nullable(); // C001
            $table->text('attachment')->nullable();
            $table->date('delivery_date');
            $table->text('remarks')->nullable();
            SchemaHelper::addCommonColumns($table, 1); // 0 = Closed PR, 2 = For Receive, 3 = For PO, 1 = Pending

        });
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id'); //* FK to purchase request
            $table->string('item_code')->nullable();
            $table->string('item_name')->nullable();
            $table->string('item_category_code')->nullable();
            $table->string('purchasing_organization')->nullable();
            $table->string('purchasing_group')->nullable();
            $table->unsignedBigInteger('requested_quantity')->nullable();
            $table->unsignedBigInteger('price')->nullable();
            $table->string('currency')->nullable();
            $table->datetime('delivery_date')->nullable();
            $table->text('remarks')->nullable();
            SchemaHelper::addCommonColumns($table, 0);
        });

        //* w/ SAP Structure
        Schema::create('sap_purchase_request', function (Blueprint $table) {
            $table->id();
            $table->string('definition_id'); //* Static value
            $table->string('bpa_response_id')->nullable();
            $table->string('purchase_requisition_type')->nullable();
            $table->text('remarks')->nullable();
            SchemaHelper::addCommonColumns($table, 1);

        });
        Schema::create('sap_purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id'); //* FK to purchase_request
            $table->unsignedBigInteger('purchase_requisition_item'); //* by 10's incrementation
            $table->string('material')->nullable(); //* Item Code
            $table->string('material_group')->nullable(); //* Item Category Code
            $table->string('plant')->nullable(); //* Store Code
            $table->string('company_code')->nullable(); //* Store Company Code
            $table->string('purchasing_organization')->nullable();
            $table->string('purchasing_group')->nullable();
            $table->unsignedBigInteger('requested_quantity')->nullable();
            $table->unsignedBigInteger('purchase_requisition_price')->nullable(); //* Price
            $table->string('purchase_requisition_item_currency')->nullable();//* Currency
            $table->datetime('delivery_date')->nullable(); //* Expected Delivery Date
            $table->string('storage_location')->nullable();
            $table->text('purchase_requisition_item_text')->nullable(); //* Remarks
            SchemaHelper::addCommonColumns($table, 0);
        });

        Schema::create('purchase_request_cache', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->string('uuid');
            $table->string('store_code');
            $table->string('store_sub_unit_short_name')->nullable();
            $table->longText('items');
            $table->date('delivery_date');
            $table->text('remarks')->nullable();
            $table->text('attachment')->nullable();
            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('sap_purchase_request');
        Schema::dropIfExists('sap_purchase_request_items');
        Schema::dropIfExists('purchase_request_cache');
    }
};