<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOutItemModel extends Model
{
    use HasFactory;

    protected $table = 'stock_out_items';
    protected $fillable = [
        'stock_out_id',
        'item_code',
        'item_description',
        'item_category_name',
        // 'unit_of_measure',
        // 'item_variant_name',
        'quantity',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function stockOut()
    {
        return $this->belongsTo(StockOutModel::class);
    }

    public function stockInventory()
    {
        return $this->belongsTo(StockInventoryModel::class, 'item_code', 'item_code');
    }
}