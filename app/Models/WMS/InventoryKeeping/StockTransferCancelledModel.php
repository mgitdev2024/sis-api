<?php

namespace App\Models\WMS\InventoryKeeping;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferCancelledModel extends Model
{
    use HasFactory;

    protected $table = 'wms_stock_transfer_cancelled';

    protected $fillable = [
        'stock_transfer_list_id',
        'reason',
        'attachment',
    ];

    public function stockTransferList()
    {
        return $this->belongsTo(StockTransferListModel::class, 'stock_transfer_list_id');
    }
}
