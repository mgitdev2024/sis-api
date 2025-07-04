<?php

namespace App\Models\DirectPurchase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class DirectPurchaseHandledItemModel extends Model
{
    use HasFactory;

    protected $table = 'direct_purchase_handled_items';

    protected $appends = ['type_label', 'formatted_created_at_label', 'formatted_updated_at_label', 'formatted_received_date_label'];

    protected $fillable = [
        'direct_purchase_item_id',
        'delivery_receipt_number',
        'quantity',
        'storage',
        'remarks',
        'type',   // 0 = rejected, 1 = received
        'status', // 0 = pending, 1 = posted, 2 = deleted
        'expiration_date',
        'received_date',
        'created_at',
        'updated_at',
        'created_by_id',
        'updated_by_id',
    ];

    public function directPurchaseItem()
    {
        return $this->belongsTo(DirectPurchaseItemModel::class);
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

    public function getFormattedReceivedDateLabelAttribute()
    {
        return $this->received_date
            ? Carbon::parse($this->received_date)->format('F d, Y')
            : null;
    }
}
