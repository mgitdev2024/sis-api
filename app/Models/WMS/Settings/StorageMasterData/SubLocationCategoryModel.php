<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubLocationCategoryModel extends Model
{
    use HasFactory;

    protected $table = 'wms_storage_sub_location_categories';
    protected $fillable = [
        'code',
        'number',
        'has_layer',
        'layers',
        'sub_location_type_id',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
