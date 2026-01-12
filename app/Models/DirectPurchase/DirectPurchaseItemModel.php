<?php

namespace App\Models\DirectPurchase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class DirectPurchaseItemModel extends Model
{
    use HasFactory;
    protected $table = 'direct_purchase_items';
    protected $appends = [
        'formatted_created_at_label',
        'formatted_updated_at_label',
        // 'total_received_quantity_label'
    ];

    protected $fillable = [
        'direct_purchase_id',
        'item_code',
        'item_description',
        'item_category_code',
        'quantity',
        'uom',
        'status',
        'remarks',
        'created_at',
        'updated_at',
        'created_by_id',
        'updated_by_id',
    ];

    public function directPurchase()
    {
        return $this->belongsTo(DirectPurchaseModel::class);
    }

    // public function directPurchaseHandledItems()
    // {
    //     return $this->hasMany(DirectPurchaseHandledItemModel::class, 'direct_purchase_item_id');
    // }

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
    // public function getTotalReceivedQuantityLabelAttribute()
    // {
    //     $directPurchaseHandledItems = $this->directPurchaseHandledItems;
    //     $totalReceivedQuantity = 0;

    //     foreach ($directPurchaseHandledItems as $item) {
    //         if ($item->type == 1) { // Only count received items
    //             $totalReceivedQuantity += $item->quantity;
    //         }
    //     }

    //     return $totalReceivedQuantity;
    // }
}
