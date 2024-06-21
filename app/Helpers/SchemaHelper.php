<?php

namespace App\Helpers;

use Illuminate\Database\Schema\Blueprint;

class SchemaHelper
{
    public static function addCommonColumns(Blueprint $table, $status = 1)
    {
        $table->tinyInteger('status')->default($status);
        $table->string('created_by_id');
        $table->string('updated_by_id')->nullable();
        $table->timestamps();
    }
    public static function addCodeShortLongNameColumns(Blueprint $table)
    {
        $table->string('code');
        $table->string('short_name')->nullable();
        $table->string('long_name')->nullable();
    }
}
