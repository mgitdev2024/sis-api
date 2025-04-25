<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInventoryModel extends Model
{
    use HasFactory;

    protected $table = 'stock_inventories';

    protected $fillable = [
        'store_code',
        'store_sub_unit_short_name',
        'item_code',
        'item_description',
        'stock_count',
        'status',
        'created_by_id',
        'updated_by_id',
    ];
}
