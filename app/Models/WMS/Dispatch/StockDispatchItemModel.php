<?php

namespace App\Models\WMS\Dispatch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockDispatchItemModel extends Model
{
    use HasFactory;

    protected $table = 'wms_stock_dispatch_items';

    protected $fillable = [
        'stock_dispatch_id',
        'store_id',
        'store_name',
        'dispatch_items',
    ];

    public function stockDispatch()
    {
        return $this->belongsTo(StockDispatchModel::class, 'stock_dispatch_id');
    }
}
