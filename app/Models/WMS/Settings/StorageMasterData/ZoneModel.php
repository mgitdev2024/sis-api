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
    public function facilty()
    {
        return $this->belongsTo(FacilityPlantModel::class,'facility_id', 'id');
    }

}
