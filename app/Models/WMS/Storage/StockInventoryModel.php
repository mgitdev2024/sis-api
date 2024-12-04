<?php

namespace App\Models\WMS\Storage;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInventoryModel extends Model
{
    use HasFactory;
    protected $table = 'wms_stock_inventories';
    protected $appends = ['item_code'];
    protected $fillable = [
        'item_id',
        'stock_count',
    ];

    public function itemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_id');
    }

    public function getItemCodeAttribute()
    {
        $itemMasterdataModel = ItemMasterdataModel::find($this->item_id);
        return $itemMasterdataModel->item_code;
    }
}
