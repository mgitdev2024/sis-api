<?php

use App\Helpers\SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unmet_demands', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code');
            $table->text('store_code');
            $table->text('store_sub_unit_short_name');
            SchemaHelper::addCommonColumns($table); // 0 = Cancelled, 1 = Active
        });

        Schema::create('unmet_demand_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unmet_demand_id');
            $table->string('item_code');
            $table->string('item_description'); // Cheeseroll Box of 12
            $table->string('item_category_name');
            $table->integer('quantity'); // 12
            SchemaHelper::addCommonColumns($table); // 0 = Cancelled, 1 = Active

            $table->foreign('unmet_demand_id')->references('id')->on('unmet_demands')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unmet_demands');
        Schema::dropIfExists('unmet_demand_items');
    }
};
