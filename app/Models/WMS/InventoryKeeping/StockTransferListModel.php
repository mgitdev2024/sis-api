<?php

namespace App\Models\WMS\InventoryKeeping;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferListModel extends Model
{
    use HasFactory;

    protected $table = 'wms_stock_transfer_lists';

    protected $fillable = [
        'reference_number',
        'requested_item_count',
        'reason',
    ];

    public function getStatusAttribute()
    {
        return $this->attributes['status'] == 0 ? 'Pending' : 'Completed';
    }
}
