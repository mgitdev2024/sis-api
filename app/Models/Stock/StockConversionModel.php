<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockConversionModel extends Model
{
    use HasFactory;
    protected $table = 'stock_conversions';

    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'batch_code',
        'item_code',
        'item_description',
        'item_category_name',
        'quantity',
        'converted_quantity',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public static function onGenerateReferenceNumber()
    {
        $latestDirectPurchaseId = static::latest()->value('id');
        $nextDirectPurchaseId = $latestDirectPurchaseId + 1;
        $referenceNumber = 'STC-' . str_pad($nextDirectPurchaseId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

    public function stockConversionItems()
    {
        return $this->hasMany(StockConversionItemModel::class, 'stock_conversion_id');
    }
}
