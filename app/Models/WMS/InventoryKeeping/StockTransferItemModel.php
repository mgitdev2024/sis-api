<?php

namespace App\Models\WMS\InventoryKeeping;

use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItemModel extends Model
{
    use HasFactory;

    protected $table = 'wms_stock_transfer_items';

    protected $fillable = [
        'stock_transfer_list_id',
        'item_code',
        'selected_items',
        'initial_stock',
        'transfer_quantity',
        'zone_id',
        'sub_location_id',
        'layer',
        'origin_location'
    ];

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransferListModel::class, 'stock_transfer_list_id');
    }

    public function zone()
    {
        return $this->belongsTo(ZoneModel::class, 'zone_id');
    }

    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'sub_location_id');
    }

    public static function onGenerateOriginLocation($zoneId, $subLocationId, $layerLevel)
    {
        $subLocationModel = SubLocationModel::find($subLocationId);
        $subLocationCode = $subLocationModel->code;
        $zoneShortName = $subLocationModel->zone->short_name;

        $originLocation = "{$zoneShortName} {$subLocationCode} L{$layerLevel}";
        return $originLocation;
    }
}
