<?php

namespace App\Models\WMS\InventoryKeeping\AllocationOrder;

use App\Models\WMS\Settings\ItemMasterData\ItemDeliveryTypeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllocationOrderModel extends Model
{
    use HasFactory;
    protected $table = 'wms_allocation_orders';
    protected $fillable = [
        'reference_number',
        'estimated_delivery_date',
        'delivery_type_code',
        'consolidated_by',
        'created_by_id',
    ];

    public function allocatedItems()
    {
        return $this->hasMany(AllocationItemModel::class, 'allocated_orders_id');
    }
}
