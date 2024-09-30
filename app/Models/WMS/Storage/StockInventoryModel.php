<?php

namespace App\Models\WMS\Storage;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInventoryModel extends Model
{
    use HasFactory;
    protected $table = 'wms_stock_inventories';

    protected $fillable = [
        'item_id',
        'stock_count',
    ];

    public function itemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_id');
    }
}
