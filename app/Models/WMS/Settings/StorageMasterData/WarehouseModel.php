<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseModel extends Model
{
    use HasFactory;
    protected $table = 'wms_storage_warehouses';
    protected $fillable = [
        'facility_id',
        'code',
        'short_name',
        'long_name',
        'description',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
    protected $appends = ['facility_label'];
    public function facilty()
    {
        return $this->belongsTo(FacilityPlantModel::class,'facility_id', 'id');
    }

    public function getFacilityLabelAttribute()
    {
        return $this->facilty ? $this->facilty->long_name : null;
    }

}
