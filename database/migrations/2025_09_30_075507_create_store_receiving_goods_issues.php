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
        Schema::create('store_receiving_gi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sr_inventory_id'); // store inventory id
            $table->date('gi_posting_date')->nullable();
            $table->string('gi_plant_code');
            $table->string('gi_plant_name')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('sr_inventory_id')->references('id')->on('store_receiving_inventory')->onDelete('cascade');
        });

        Schema::create('store_receiving_gi_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sr_inventory_item_id');
            $table->string('gi_id')->nullable();
            $table->string('gi_material_doc_year')->nullable();
            $table->string('gi_material_doc')->nullable();
            $table->date('gi_posting_date')->nullable();
            $table->string('gi_inventory_stock_type')->nullable();
            $table->string('gi_inventory_trans_type')->nullable();
            $table->string('gi_batch')->nullable();
            $table->date('gi_shelf_life_exp_date')->nullable();
            $table->date('gi_manu_date')->nullable();
            $table->string('gi_goods_movement_type')->nullable();
            $table->string('gi_purchase_order')->nullable();
            $table->string('gi_purchase_order_item')->nullable();
            $table->string('gi_entry_unit')->nullable();
            $table->string('gi_supplying_plant')->nullable();
            SchemaHelper::addCommonColumns($table);

            $table->foreign('sr_inventory_item_id')->references('id')->on('store_receiving_inventory_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_receiving_gi');
        Schema::dropIfExists('store_receiving_gi_items');
    }
};
