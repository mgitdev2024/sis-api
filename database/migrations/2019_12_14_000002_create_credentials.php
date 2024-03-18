<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique();
            $table->string('password')->nullable();
            $table->tinyInteger('is_first_login')->default(1);
            $table->text('signed_route')->nullable();
            $table->text('otp')->nullable();
            $table->tinyInteger('is_locked')->default(0);
            $table->unsignedBigInteger('user_access_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('credentials');
    }
};
