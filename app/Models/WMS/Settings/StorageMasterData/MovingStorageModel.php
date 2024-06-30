<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovingStorageModel extends Model
{
    use HasFactory;
    protected $table = 'wms_storage_moving_storages';
    protected $fillable = [
        'facility_id',
        'warehouse_id',
        'zone_id',
        'sub_location_category_id',
        'code',
        'short_name',
        'long_name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
