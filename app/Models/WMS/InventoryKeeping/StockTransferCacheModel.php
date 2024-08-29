<?php

namespace App\Models\WMS\InventoryKeeping;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferCacheModel extends Model
{
    use HasFactory;

    protected $table = 'wms_stock_transfer_cache';

    protected $fillable = [
        'requested_item_count',
        // 'reason',
        'stock_transfer_items',
        'created_by_id'
    ];
}
