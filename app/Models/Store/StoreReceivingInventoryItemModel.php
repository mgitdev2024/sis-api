<?php

namespace App\Models\Store;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class StoreReceivingInventoryItemModel extends Model
{
    use HasFactory;

    protected $table = "store_receiving_inventory_items";

    protected $appends = [
        'status_label',
        'type_label',
        'receive_type_label',
        'order_type_label',
        'completed_by_label',
        'completed_at_label',
        'delivery_date_label',
    ];
    protected $fillable = [
        'store_receiving_inventory_id',
        'reference_number',
        'order_type',
        'fan_out_category',
        'is_wrong_drop',
        'store_code',
        'store_name',
        'delivery_date',
        'delivery_type',
        // 'store_sub_unit_id',
        'store_sub_unit_short_name',
        'store_sub_unit_long_name',
        'order_date',
        'item_code',
        'item_description',
        'item_category_name',
        'order_quantity',
        'allocated_quantity',
        'received_quantity',
        'received_items',
        'receive_type',
        'type',
        'is_received',
        'created_by_name',
        'created_by_id',
        'updated_by_id',
        'status',

        // Added Fields
        'order_session_id',
        'completed_by_id',
        'completed_at',
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

    public function getTypeLabelAttribute()
    {
        $typeArr = [
            0 => 'Order',
            1 => 'Pull-Out Transfer',
            2 => 'Store Transfer',
        ];
        return $typeArr[$this->type] ?? 'Unknown';
    }

    public function getOrderTypeLabelAttribute()
    {
        $orderTypeArr = [
            0 => 'Regular',
            1 => 'Special',
            2 => 'Fan-Out',
        ];

        return $orderTypeArr[$this->order_type] ?? null;
    }

    public function getCompletedByLabelAttribute()
    {
        if ($this->completed_by_id) {
            $user = User::where('employee_id', $this->completed_by_id)->first();
            return $user ? "$user->first_name $user->last_name" : 'Unknown';
        }
        return 'Not Completed';
    }

    public function getCompletedAtLabelAttribute()
    {
        return $this->completed_at ? Carbon::parse($this->completed_at)->format('M d, Y h:i A') : 'Not Completed';
    }

    public function getDeliveryDateLabelAttribute()
    {
        return $this->delivery_date ? Carbon::parse($this->delivery_date)->format('M d, Y') : 'Not Set';
    }
}
