<?php

namespace App\Models\WMS\Dispatch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockDispatchModel extends Model
{
    use HasFactory;

    protected $table = 'wms_stock_dispatch';

    protected $fillable = [
        'reference_number',
        'generate_picklist_id',
    ];

    public function items()
    {
        return $this->hasMany(StockDispatchItemModel::class, 'stock_dispatch_id');
    }

    public static function onGenerateStockDispatchReferenceNumber()
    {
        $latestStockRequest = static::latest()->value('id');
        $nextStockRequest = $latestStockRequest + 1;
        $referenceNumber = '7' . str_pad($nextStockRequest, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }
}
