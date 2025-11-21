<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockConversionItemModel extends Model
{
    use HasFactory;

    protected $table = 'stock_conversion_items';

    protected $fillable = [
        'stock_conversion_id',
        'item_code',
        'item_description',
        'item_category_name',
        'quantity',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function stockConversion()
    {
        return $this->belongsTo(StockConversionModel::class, 'stock_conversion_id');
    }

    public function stockInventory()
    {
        return $this->belongsTo(StockInventoryModel::class, 'item_code', 'item_code');
    }
}
