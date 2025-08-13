<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReturnItemModel extends Model
{
    use HasFactory;

    protected $table = 'stock_return_items';
    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'item_code',
        'quantity',
        'status',
        'created_at',
        'updated_at',
        'created_by_id',
        'updated_by_id',
    ];

    public static function onGenerateReferenceNumber()
    {
        $latestStockReturnId = static::latest()->value('id');
        $nextStockReturnId = $latestStockReturnId + 1;
        $referenceNumber = 'RT-' . str_pad($nextStockReturnId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }
}
