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
        Schema::create('wms_generate_picklists', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->unsignedBigInteger('allocation_order_id');
            $table->string('consolidation_reference_number');

            SchemaHelper::addCommonColumns($table, 0); // 0 = pending, 1 = complete
            $table->foreign('allocation_order_id')->references('id')->on('wms_allocation_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wms_generate_picklists');
    }
};
