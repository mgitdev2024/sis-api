<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubLocationTypeModel extends Model
{
    use HasFactory;

    protected $table = 'wms_storage_sub_location_type';
    protected $fillable = [
        'code',
        'short_name',
        'long_name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
