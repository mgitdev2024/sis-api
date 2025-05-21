<?php

namespace App\Models\PurchaseOrder;

use App\Http\Controllers\v1\PurchaseOrder\PurchaseOrderItemController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class PurchaseOrderModel extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $appends = ['status_label', 'formatted_created_at_label', 'formatted_updated_at_label'];
    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'supplier_code',
        'supplier_name',
        'purchase_order_date',
        'expected_delivery_date',
        'status',
        'created_at',
        'updated_at',
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
