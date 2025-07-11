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
        Schema::create('stock_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            $table->string('item_code');
            $table->string('item_description');
            $table->string('item_category_name');
            $table->integer('stock_count');
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number'); // RE-807231987321
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            $table->string('item_code');
            $table->string('item_description');
            $table->string('item_category_name');
            $table->integer('quantity');
            $table->integer('initial_stock');
            $table->integer('final_stock');
            $table->longText('transaction_items')->nullable();
            $table->string('transaction_type'); // IN OUT
            $table->string('transaction_sub_type')->nullable(); // RECEIVED RETURNED TRANSFERRED CONVERTED
            SchemaHelper::addCommonColumns($table);
        });

        Schema::create('stock_received_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_receiving_inventory_item_id');
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            $table->string('item_code');
            $table->string('item_description');
            $table->string('item_category_name');
            $table->string('batch_id');
            $table->longText('received_items')->nullable();

            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_inventories');
        Schema::dropIfExists('stock_logs');
        Schema::dropIfExists('stock_received_items');

    }
};
