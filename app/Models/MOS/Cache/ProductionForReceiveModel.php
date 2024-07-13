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
        'scanned_items_qr',
        'temporary_storage_id',
        'created_by_id'
    ];
    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class);
    }
}
