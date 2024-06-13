<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use App\Models\WMS\Storage\QueuedSubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use App\Models\WMS\Storage\StockLogModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubLocationModel extends Model
{
    use HasFactory;

    protected $table = 'wms_storage_sub_locations';
    protected $appends = ['facility_label', 'warehouse_label', 'zone_label'];

    protected $fillable = [
        'code',
        'number',
        'has_layer',
        'layers',
        'is_permanent',
        'sub_location_type_id',
        'facility_id',
        'warehouse_id',
        'zone_id',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
    public function facility()
    {
        return $this->belongsTo(FacilityPlantModel::class, 'facility_id', 'id');
    }
    public function warehouse()
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id', 'id');
    }
    public function zone()
    {
        return $this->belongsTo(ZoneModel::class, 'zone_id', 'id');
    }
    public function stockLogs()
    {
        return $this->hasMany(StockLogModel::class, 'sub_location_id', 'id');
    }
    public function getFacilityLabelAttribute()
    {
        return $this->facility ? $this->facility->long_name : null;
    }
    public function getWarehouseLabelAttribute()
    {
        return $this->warehouse ? $this->warehouse->long_name : null;
    }
    public function getZoneLabelAttribute()
    {
        return $this->zone ? $this->zone->long_name : null;
    }
    public function queuedTemporaryStorages()
    {
        return $this->hasMany(QueuedTemporaryStorageModel::class, 'sub_location_id', 'id');
    }

    public function queuedSubLocations()
    {
        return $this->hasMany(QueuedSubLocationModel::class, 'sub_location_id', 'id');
    }
}
