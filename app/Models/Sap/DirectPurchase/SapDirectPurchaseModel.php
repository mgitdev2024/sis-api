<?php

namespace App\Models\Sap\DirectPurchase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapDirectPurchaseModel extends Model
{
    use HasFactory;

    protected $table = 'sap_direct_purchases';
    protected $fillable = [
        'definition_id',
        'bpa_response_id',
        'purchase_requisition_type',
        'remarks',
        'status',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at'
    ];

    public static function onGenerateReferenceNumber()
    {
        $latestPurchaseRequestId = static::latest()->value('id');
        $nextPurchaseRequestId = $latestPurchaseRequestId + 1;
        $referenceNumber = 'DP-' . str_pad($nextPurchaseRequestId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

    public function sapDirectPurchaseItems()
    {
        return $this->hasMany(SapDirectPurchaseItemModel::class, 'direct_purchase_id');
    }
}
