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
        Schema::create('stock_inventory_count_template', function (Blueprint $table) {
            $table->id();
            $table->string('store_code');
            $table->string('store_sub_unit_short_name')->nullable(); // FOH BOH
            $table->longText('selection_template'); // {"Bakery":{"Additionals":[{"item_code":"BCD B","long_name":"Butter Cream Brown ","department_short_name":"Bakery","category_name":"Additionals"},{"item_code":"BCD-W","long_name":"Butter Cream White","department_short_name":"Bakery","category_name":"Additionals"}]
            SchemaHelper::addCommonColumns($table, 0); // 0 = Pending, 1 = Closed / Complete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_inventory_count_template');
    }
};
