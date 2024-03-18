<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('personal_informations', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->string('prefix')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('alias')->nullable();
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->integer('age')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('company_email')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            // Define the foreign key constraint
            $table->foreign('employee_id')->references('employee_id')->on('credentials')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('personal_informations');
    }
};
