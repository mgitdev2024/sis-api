<?php

namespace App\Helpers;

use Illuminate\Database\Schema\Blueprint;

class SchemaHelper
{
    public static function addCommonColumns(Blueprint $table)
    {
        $table->tinyInteger('status')->default(1);
        $table->unsignedBigInteger('created_by_id');
        $table->unsignedBigInteger('updated_by_id')->nullable();
        $table->timestamps();
    }
    public static function addCodeShortLongNameColumns(Blueprint $table)
    {
        $table->string('code')->nullable();
        $table->string('short_name')->nullable();
        $table->string('long_name')->nullable();
    }
}
