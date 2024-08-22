<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneModel extends Model
{
    use HasFactory;
    protected $table = 'wms_storage_zones';
    protected $fillable = [
        'facility_id',
        'warehouse_id',
        'code',
        'storage_type_id',
        'short_name',
        'long_name',
        'description',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
    public function storageType()
    {
        return $this->belongsTo(StorageTypeModel::class, 'storage_type_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function subLocations()
    {
        return $this->hasMany(SubLocationModel::class, 'zone_id');
    }
}
