<?php

namespace App\Models\WMS\InventoryKeeping\ForStockTransfer;

use App\Models\WMS\InventoryKeeping\StockTransferItemModel;
use App\Models\WMS\InventoryKeeping\StockTransferListModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequestForTransferModel extends Model
{
    use HasFactory;
    protected $table = 'wms_stock_request_for_transfer';

    // Define the columns that are mass assignable
    protected $fillable = [
        'stock_transfer_list_id',
        'stock_transfer_item_id',
        // 'item_code',
        'scanned_items',
        'sub_location_id',
        'layer_level',
        'created_by_id',
    ];

    // Define any relationships you may need
    public function stockTransferList()
    {
        return $this->belongsTo(StockTransferListModel::class, 'stock_transfer_list_id');
    }

    public function stockTransferItem()
    {
        return $this->belongsTo(StockTransferItemModel::class, 'stock_transfer_item_id');
    }

    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'sub_location_id');
    }
}
