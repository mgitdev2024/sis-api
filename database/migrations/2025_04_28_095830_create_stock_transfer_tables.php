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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number'); // ST-807231987321, PT-807231987321
            $table->string('store_code'); // C001
            $table->string('store_sub_unit_short_name'); // FOH BOH
            $table->tinyInteger('transfer_type'); // 0 = Store Transfer, 1 = Pull Out
            $table->tinyInteger('transportation_type')->nullable(); // 1: Logistics, 2: Third Party
            $table->date('pickup_date');
            $table->string('location_code')->nullable(); // C001, BK-BREADS, BK
            $table->string('location_name')->nullable(); // Arcovia, Bakery, etc
            $table->string('location_sub_unit')->nullable(); // C001
            $table->string('remarks')->nullable();
            $table->text('attachment')->nullable();
            SchemaHelper::addCommonColumns($table); // 0 = Cancelled, 1 = For Receive, 2 = Received
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id'); // FK to stock_transfers
            $table->string('item_code'); // CR 12
            $table->string('item_description'); // Cheeseroll Box of 12
            $table->string('item_category_name');
            $table->integer('quantity'); // 12
            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_transfer_items');
    }
};
