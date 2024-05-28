<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubLocationCategoryLayerModel extends Model
{
    use HasFactory;
    protected $table = 'wms_storage_sub_location_category_layers';
    protected $fillable = [
        'sub_location_category_id',
        'min',
        'max',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
