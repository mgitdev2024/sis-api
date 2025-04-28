<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreReceivingInventoryItemModel extends Model
{
    use HasFactory;

    protected $table = "store_receiving_inventory_items";

    protected $appends = ['status_label', 'type_label', 'receive_type_label'];
    protected $fillable = [
        'store_receiving_inventory_id',
        'reference_number',
        'is_special',
        'is_wrong_drop',
        'store_code',
        'store_name',
        'delivery_date',
        'delivery_type',
        'store_sub_unit_id',
        'store_sub_unit_short_name',
        'store_sub_unit_long_name',
        'order_date',
        'item_code',
        'item_description',
        'order_quantity',
        'allocated_quantity',
        'received_quantity',
        'received_items',
        'receive_type',
        'type',
        'created_by_name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    public function storeReceivingInventory()
    {
        return $this->belongsTo(StoreReceivingInventoryModel::class, 'store_receiving_inventory_id');
    }
    public function getStatusLabelAttribute()
    {
        return $this->status == 1 ? 'Complete' : 'Pending';
    }

    public function getReceiveTypeLabelAttribute()
    {
        return $this->status == 0 ? 'Scan' : 'Manual';
    }


    public function geTypeLabelAttribute()
    {
        $typeArr = [
            0 => 'Order',
            1 => 'Pull-Out Transfer',
            2 => 'Store Transfer',
        ];
        return $typeArr[$this->type] ?? 'Unknown';
    }
}
