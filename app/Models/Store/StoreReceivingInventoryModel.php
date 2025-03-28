<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreReceivingInventoryModel extends Model
{
    use HasFactory;

    protected $table = 'store_receiving_inventory';

    // Fillable attributes
    protected $fillable = [
        'consolidated_order_id',
        'warehouse_code',
        'store_code',
        'store_name',
        'delivery_date',
        'delivery_type',
        'order_date',
        'item_code',
        'order_quantity',
        'received_quantity',
        'received_items',
        'created_by_name',
        'created_by_id',
        'updated_by_id'
    ];
}
