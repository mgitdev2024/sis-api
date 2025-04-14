<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreReceivingInventoryItemModel extends Model
{
    use HasFactory;

    protected $table = "store_receiving_inventory_items";

    protected $appends = ['status_label'];
    protected $fillable = [
        'store_receiving_inventory_id',
        'order_session_id',
        'is_special',
        'is_wrong_drop',
        'store_code',
        'store_name',
        'delivery_date',
        'delivery_type',
        'order_date',
        'item_code',
        'item_description',
        'order_quantity',
        'received_quantity',
        'received_items',
        'created_by_name',
        'created_by_id',
        'updated_by_id'
    ];

    public function storeReceivingInventory()
    {
        return $this->belongsTo(StoreReceivingInventoryModel::class, 'store_receiving_inventory_id');
    }
    public function getStatusLabelAttribute()
    {
        return $this->status == 1 ? 'Complete' : 'Pending';
    }
}
