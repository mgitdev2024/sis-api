<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreReceivingInventoryItemCacheModel extends Model
{
    use HasFactory;

    protected $table = 'store_receiving_items_cache';

    protected $fillable = [
        'order_session_id',
        'store_code',
        'scanned_items',
        'status',
        'created_by_id',
        'updated_by_id',
    ];
}
