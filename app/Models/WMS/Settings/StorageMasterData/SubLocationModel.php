<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubLocationModel extends Model
{
    use HasFactory;
    
    protected $table = 'wms_storage_sub_locations';
    protected $fillable = [
        'facility_id',
        'warehouse_id',
        'zone_id',
        'code',
        'short_name',
        'long_name',
        'qty',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
