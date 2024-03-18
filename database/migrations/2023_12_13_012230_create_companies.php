<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('company_code')->unique();
            $table->string('company_short_name')->nullable();
            $table->string('company_long_name');
            $table->string('company_level')->nullable();
            $table->string('tin_no');
            $table->string('sec_no');
            $table->date('sec_registered_date');
            $table->string('registered_address');
            $table->text('transactional_considerations')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('created_by_id')->references('id')->on('personal_informations');
            $table->foreign('updated_by_id')->references('id')->on('personal_informations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
