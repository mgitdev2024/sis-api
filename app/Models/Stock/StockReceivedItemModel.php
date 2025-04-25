<?php

namespace App\Models\Stock;

use App\Models\Store\StoreReceivingInventoryItemModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReceivedItemModel extends Model
{
    use HasFactory;

    protected $table = 'stock_received_items';

    protected $fillable = [
        'store_receiving_inventory_item_id',
        'store_code',
        'store_sub_unit_short_name',
        'item_code',
        'item_description',
        'batch_id',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function receivingItem()
    {
        return $this->belongsTo(StoreReceivingInventoryItemModel::class, 'store_receiving_inventory_item_id');
    }
}
