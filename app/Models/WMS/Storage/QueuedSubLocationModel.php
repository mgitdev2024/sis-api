<?php

namespace App\Models\WMS\Storage;

use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueuedSubLocationModel extends Model
{
    use HasFactory;

    protected $table = 'wms_queued_sub_locations';

    protected $fillable = [
        'sub_location_id',
        'layer_level',
        'production_items',
        'quantity',
        'storage_remaining_space'
    ];

    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'sub_location_id', 'id');
    }
}
