<?php

namespace App\Models\WMS\InventoryKeeping\StockTransfer;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItemModel extends Model
{
    use HasFactory;

    protected $table = 'wms_stock_transfer_items';

    protected $appends = ['item_code_label'];

    protected $fillable = [
        'stock_transfer_list_id',
        'item_id',
        'selected_items',
        'initial_stock',
        'transfer_quantity',
        'transferred_items',
        'substandard_items',
        'zone_id',
        'sub_location_id',
        'layer',
        'origin_location',
        'temporary_storage_id'
    ];

    public function stockTransferList()
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

    public function ItemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_id');
    }

    public static function onGenerateOriginLocation($subLocationId, $layerLevel)
    {
        $subLocationModel = SubLocationModel::find($subLocationId);
        $subLocationCode = $subLocationModel->code;
        $zoneShortName = $subLocationModel->zone->short_name;

        $originLocation = "{$zoneShortName} {$subLocationCode} L{$layerLevel}";
        return $originLocation;
    }

    public function getItemCodeLabelAttribute()
    {
        $itemCode = ItemMasterdataModel::where('id', $this->item_id)->value('item_code');
        return $itemCode;
    }
}
