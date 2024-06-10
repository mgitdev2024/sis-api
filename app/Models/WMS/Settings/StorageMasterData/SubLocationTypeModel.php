<?php

namespace App\Models\WMS\Settings\StorageMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubLocationTypeModel extends Model
{
    use HasFactory;

    protected $table = 'wms_storage_sub_location_type';
    protected $fillable = [
        'code',
        'short_name',
        'long_name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'id', 'sub_location_type_id');
    }
    public function toArray()
    {
        $array = parent::toArray();
        $array['qty'] = $this->subLocation ? $this->subLocation->count() : 0;
        $array['sub_location'] = $this->subLocation ?? [];
        return $array;
    }
    
}
