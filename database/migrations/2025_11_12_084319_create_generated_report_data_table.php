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
        Schema::create('generated_report_data', function (Blueprint $table) {
            $table->id();
            $table->text('model_name');
            $table->text('store_code')->nullable();
            $table->text('store_sub_unit_short_name')->nullable();
            $table->string('department_id')->nullable();
            $table->longText('report_data')->nullable();
            $table->string('date_range')->nullable();
            $table->string('uuid')->nullable();
            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_report_data');
    }
};