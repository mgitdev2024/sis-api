<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInventoryItemCountModel extends Model
{
    use HasFactory;

    protected $table = 'stock_inventory_items_count';

    protected $fillable = [
        'stock_inventory_count_id',
        'item_code',
        'item_description',
        'item_category_name',
        'system_quantity',
        'counted_quantity',
        'discrepancy_quantity',
        'created_by_id',
        'updated_by_id',
        'status',
        'remarks',
    ];

    public function stockInventoryCount()
    {
        return $this->belongsTo(StockInventoryCountModel::class);
    }
}
