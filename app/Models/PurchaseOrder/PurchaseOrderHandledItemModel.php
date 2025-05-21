<?php

namespace App\Models\PurchaseOrder;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class PurchaseOrderHandledItemModel extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_handled_items';

    protected $appends = ['type_label', 'formatted_created_at_label', 'formatted_updated_at_label'];

    protected $fillable = [
        'purchase_order_item_id',
        'delivery_receipt_number',
        'quantity',
        'storage',
        'remarks',
        'type',   // 0 = rejected, 1 = received
        'status',
        'expiration_date',
        'received_date',
        'created_at',
        'updated_at',
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

    public function getFormattedCreatedAtLabelAttribute()
    {
        return $this->created_at
            ? Carbon::parse($this->created_at)->format('F d, Y h:i A')
            : null;
    }

    public function getFormattedUpdatedAtLabelAttribute()
    {
        return $this->updated_at
            ? Carbon::parse($this->updated_at)->format('F d, Y h:i A')
            : null;
    }
}
