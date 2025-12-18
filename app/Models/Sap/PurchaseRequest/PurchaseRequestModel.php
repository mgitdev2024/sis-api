<?php

namespace App\Models\Sap\PurchaseRequest;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestModel extends Model
{
    use HasFactory;
    protected $table = 'purchase_request';
    protected $fillable = [
        'reference_number',
        'type',
        'delivery_date',
        'remarks',
        'store_code',
        'store_sub_unit_short_name',
        'status',  // * 0 = Closed PR, 2 = For Receive, 3 = For PO, 1 = Pending
        'attachment',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at'
    ];

    public static function onGenerateReferenceNumber()
    {
        $latestPurchaseRequestId = static::latest()->value('id');
        $nextPurchaseRequestId = $latestPurchaseRequestId + 1;
        $referenceNumber = 'PR-' . str_pad($nextPurchaseRequestId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

    public function purchaseRequestItems()
    {
        return $this->hasMany(PurchaseRequestItemModel::class, 'purchase_request_id');
    }
}