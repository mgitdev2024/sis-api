<?php

namespace App\Models\WMS\InventoryKeeping\AllocationOrder;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllocationItemModel extends Model
{
    use HasFactory;
    protected $table = 'wms_allocation_items';
    protected $appends = ['item_label'];
    protected $fillable = [
        'allocation_order_id',
        'item_id',
        'request_type',
        'theoretical_soh',
        'total_order_quantity',
        'store_order_details',
        'excess_stocks',
        'allocated_stocks',
        'created_by_id'
    ];
    public function allocatedOrder()
    {
        return $this->belongsTo(AllocationOrderModel::class, 'allocated_orders_id');
    }
    public function itemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_id');
    }
    public function getItemLabelAttribute()
    {
        return $this->itemMasterdata->item_code;
    }
}
