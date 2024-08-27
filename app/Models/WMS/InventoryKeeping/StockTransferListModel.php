<?php

namespace App\Models\WMS\InventoryKeeping;

use App\Models\User;
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

    // public function getStatusAttribute()
    // {
    //     return $this->attributes['status'] == 0 ? 'Pending' : 'Completed';
    // }
    public static function onGenerateStockRequestReferenceNumber()
    {
        $latestStockRequest = static::latest()->value('id');
        $nextStockRequest = $latestStockRequest + 1;
        $referenceNumber = 'ST-7' . str_pad($nextStockRequest, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

    public function stockTransferItems()
    {
        return $this->hasMany(StockTransferItemModel::class, 'stock_transfer_list_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'employee_id');
    }
}
