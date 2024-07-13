<?php

namespace App\Models\MOS\Cache;

use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionForReceiveModel extends Model
{
    use HasFactory;
    protected $table = 'mos_production_for_receive';
    protected $fillable = [
        'production_items',
        'sub_location_id',
        'production_type',
        'created_by_id'
    ];
    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class);
    }
}
