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
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    public function facility()
    {
        return $this->belongsTo(FacilityPlantModel::class,'facility_id', 'id');
    }
    public function warehouse()
    {
        return $this->belongsTo(WarehouseModel::class,'warehouse_id', 'id');
    }
    public function zone()
    {
        return $this->belongsTo(ZoneModel::class,'zone_id', 'id');
    }
}
