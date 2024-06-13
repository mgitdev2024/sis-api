<?php

namespace App\Models\WMS\Storage;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLogModel extends Model
{
    use HasFactory;
    protected $table = 'wms_stock_logs';

    protected $fillable = [
        'item_code',
        'action', // 1 = In, 0 = Out;
        'quantity',
        'sub_location_id',
        'layer_level',
        'storage_remaining_space'
    ];

    public function itemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_code', 'item_code');
    }

    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'sub_location_id', 'id');
    }
}
