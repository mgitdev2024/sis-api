<?php

namespace App\Models\PurchaseOrder;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class PurchaseOrderItemModel extends Model
{
    use HasFactory;
    protected $table = 'purchase_order_items';
    protected $appends = ['formatted_created_at_label', 'formatted_updated_at_label'];

    protected $fillable = [
        'purchase_order_id',
        'item_code',
        'item_description',
        'item_category_name',
        'total_received_quantity',
        'requested_quantity',
        'status',
        'created_at',
        'updated_at',
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
