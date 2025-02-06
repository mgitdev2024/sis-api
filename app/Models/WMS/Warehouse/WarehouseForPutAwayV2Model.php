<?php

namespace App\Models\WMS\Warehouse;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseForPutAwayV2Model extends Model
{
    use HasFactory;

    protected $table = 'wms_warehouse_for_put_away_v2';

    protected $fillable = [
        'warehouse_put_away_key',
        'warehouse_receiving_reference_number',
        'item_id',
        'temporary_storage_id',
        'production_items',
        'sub_location_id',
        'layer_level',
    ];

    /**
     * Relationships
     */
    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'sub_location_id');
    }

    public function itemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_id');
    }
}
