<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityPlantModel extends Model
{
    use HasFactory;
    protected $table = 'storage_facility_plants';
    protected $fillable = [
        'code',
        'short_name',
        'long_name',
        'description',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

}
