<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOutModel extends Model
{
    use HasFactory;
    protected $table = 'stock_outs';
    protected $fillable = [
        'reference_number',
        'or_number',
        'store_code',
        'store_sub_unit_short_name',
        'stock_out_date',
        'attachment',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function stockOutItems()
    {
        return $this->hasMany(StockOutItemModel::class);
    }

    public static function onGenerateReferenceNumber()
    {
        $latestStockOutId = static::latest()->value('id');
        $nextStockOutId = $latestStockOutId + 1;
        $referenceNumber = 'SO-' . str_pad($nextStockOutId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }
}
