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
        Schema::create('qa_item_dispositions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_batch_id');
            $table->string('item_code');
            $table->integer('item_key');
            $table->tinyInteger('production_type'); // 0 = otb, 1 = ota
            $table->tinyInteger('type'); //  0 = For Investigation , 1 = For Sampling
            $table->longText('produced_items');
            $table->integer('quantity_update')->nullable();
            $table->string('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->tinyInteger('production_status')->default(1); //  0 = closed , 1 = open
            $table->tinyInteger('is_release')->default(1); //  0 = hold , 1 = not hold
            $table->integer('action')->nullable(); //  action status
            $table->integer('aging_period')->nullable();
            $table->unsignedBigInteger('fulfilled_by_id')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            SchemaHelper::addCommonColumns($table);  //  0 = closed , 1 = open

            $table->foreign('production_batch_id')->references('id')->on('mos_production_batches');
        });
        Schema::create('qa_sub_standard_items', function (Blueprint $table) {
            $table->id();
            $table->string('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->integer('location_id');

            $table->unsignedBigInteger('production_batch_id');
            $table->string('item_code');
            $table->integer('item_key');
            $table->tinyInteger('production_type'); // 0 = otb, 1 = ota
            $table->tinyInteger('item_disposition_id')->nullable();
            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_item_dispositions');
        Schema::dropIfExists('qa_sub_standard_items');
    }
};
