<?php

namespace App\Models\WMS\Warehouse;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseForPutAwayModel extends Model
{
    use HasFactory;

    protected $table = 'wms_warehouse_for_put_away';

    protected $fillable = [
        'warehouse_receiving_reference_number',
        'warehouse_put_away_id',
        'item_code',
        'production_items',
        'sub_location_id',
        'layer_level',
        'status',
        'created_by_id'
    ];

    public function warehouseReceiving()
    {
        return $this->hasMany(WarehouseReceivingModel::class, 'warehouse_receiving_reference_number');
    }

    public function warehousePutAway()
    {
        return $this->belongsTo(WarehousePutAwayModel::class, 'warehouse_put_away_id');
    }

    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'sub_location_id');
    }

    public function itemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_code', 'item_code');
    }
}
