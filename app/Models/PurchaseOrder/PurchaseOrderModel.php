<?php

namespace App\Models\PurchaseOrder;

use App\Http\Controllers\v1\PurchaseOrder\PurchaseOrderItemController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderModel extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $appends = ['status_label'];
    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'supplier_code',
        'supplier_name',
        'purchase_order_date',
        'expected_delivery_date',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItemModel::class, 'purchase_order_id');
    }


    public function getStatusLabelAttribute()
    {
        return $this->status == 1 ? 'Pending' : 'Closed';
    }
}
