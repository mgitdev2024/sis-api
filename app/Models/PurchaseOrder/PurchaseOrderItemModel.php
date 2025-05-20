<?php

namespace App\Models\PurchaseOrder;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItemModel extends Model
{
    use HasFactory;
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'item_code',
        'item_description',
        'item_category_name',
        'total_received_quantity',
        'requested_quantity',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrderModel::class);
    }

    public function purchaseOrderHandledItems()
    {
        return $this->hasMany(PurchaseOrderHandledItemModel::class, 'purchase_order_item_id');
    }
}
