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
        Schema::create('customer_return_forms', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number'); // CR00001
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            $table->string('official_receipt_number'); // OR Number
            $table->text('attachment');
            $table->string('remarks')->nullable();
            SchemaHelper::addCommonColumns($table);
        });
        Schema::create('customer_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_return_form_id'); // FK to customer_return_forms
            $table->string('item_code'); // CR 12
            $table->string('item_description'); // Cheeseroll Box of 12
            $table->string('item_category_name');
            $table->integer('quantity');
            SchemaHelper::addCommonColumns($table);

            $table->foreign('customer_return_form_id')->references('id')->on('customer_return_forms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_return_forms');
        Schema::dropIfExists('customer_return_items');

    }
};
