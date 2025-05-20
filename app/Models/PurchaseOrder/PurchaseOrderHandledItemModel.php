<?php

namespace App\Models\PurchaseOrder;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHandledItemModel extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_handled_items';

    protected $appends = ['type_label'];

    protected $fillable = [
        'purchase_order_item_id',
        'delivery_receipt_number',
        'quantity',
        'storage',
        'remarks',
        'type',   // 0 = rejected, 1 = received
        'status',
        'expiration_date',
        'created_by_id',
        'updated_by_id',
    ];

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItemModel::class);
    }

    public function getTypeLabelAttribute()
    {
        return $this->type == 1 ? 'Received' : 'Rejected';
    }
}
