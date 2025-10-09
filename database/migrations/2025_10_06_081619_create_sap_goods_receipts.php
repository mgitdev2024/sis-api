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
        Schema::create('sap_goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('definition_id');
            $table->string('bpa_response_id')->nullable();
            $table->string('goods_movement_code')->nullable();
            $table->date('posting_date'); // warehouse receiving complete transaction at & QA Created at
            $table->date('document_date')->nullable();
            $table->string('material_document_header_text')->nullable();
            $table->string('reference_document'); // reference number of warehouse receiving and QA
            $table->text('error_message')->nullable();
            $table->tinyInteger('upload_status')->default(0);
            SchemaHelper::addCommonColumns($table, 0); // 0 = Pending, 1 = Erroneous, 2 = Running, 3 = Completed
        });

        Schema::create('sap_goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sap_good_receipt_id');
            $table->string('plant');
            $table->string('material');
            $table->string('storage_location')->nullable();
            $table->string('batch')->nullable(); // Batch number
            $table->string('goods_movement_type')->nullable();
            $table->string('purchase_order'); // To be received from process order in SAP API
            $table->string('purchase_order_item')->nullable();
            $table->string('goods_movement_ref_doc_type')->nullable();
            $table->string('quantity_in_entry_unit'); // Quantity of items per batch
            $table->string('entry_unit')->nullable(); // UOM
            $table->string('manufacture_date')->nullable();
            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sap_goods_receipts');
        Schema::dropIfExists('sap_goods_receipt_items');
    }
};
