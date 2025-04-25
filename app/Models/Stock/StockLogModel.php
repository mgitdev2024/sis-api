<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLogModel extends Model
{
    use HasFactory;

    protected $table = 'stock_logs';

    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'item_code',
        'item_description',
        'quantity',
        'initial_stock',
        'final_stock',
        'transaction_items',
        'transaction_type',
        'transaction_sub_type',
        'status',
        'created_by_id',
        'updated_by_id',
    ];
}
