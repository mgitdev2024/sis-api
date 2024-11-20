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
        Schema::create('qa_item_disposition_repositories', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type'); // 0 = For Disposal, 1 = For Consumption, 2 = For Endorsement,
            $table->unsignedBigInteger('production_batch_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('quantity');
            SchemaHelper::addCommonColumns($table); //  0 = closed, 1 = open,

            $table->foreign('item_id')->references('id')->on('wms_item_masterdata');
            $table->foreign('production_batch_id')->references('id')->on('mos_production_batches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_item_disposition_repositories');
    }
};
