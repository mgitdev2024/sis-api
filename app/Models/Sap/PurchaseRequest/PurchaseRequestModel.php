<?php

namespace App\Models\Sap\PurchaseRequest;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class PurchaseRequestModel extends Model
{
    use HasFactory;
    protected $table = 'purchase_request';
    protected $appends = [
        'formatted_created_at_label',
        'formatted_updated_at_label',
        'created_by_name_label',
        'updated_by_name_label',
    ];
    protected $fillable = [
        'reference_number',
        'type',
        'delivery_date',
        'remarks',
        'store_code',
        'store_sub_unit_short_name',
        'storage_location',
        'status',  // * 0 = Closed PR / Posted , 1 = For PO, 2 = For Receive, 3 = Cancelled
        'attachment',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'attachment' => 'array',
    ];
    public static function onGenerateReferenceNumber()
    {
        $latestPurchaseRequestId = static::latest()->value('id');
        $nextPurchaseRequestId = $latestPurchaseRequestId + 1;
        $referenceNumber = 'PR-' . str_pad($nextPurchaseRequestId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
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
    public function getCreatedByNameLabelAttribute()
    {
        $userModel = \App\Models\User::where('employee_id', $this->created_by_id)->first();
        if ($userModel) {
            return $userModel->first_name . ' ' . $userModel->last_name;
        }
        return null;
    }
    public function getUpdatedByNameLabelAttribute()
    {
        $userModel = \App\Models\User::where('employee_id', $this->updated_by_id)->first();
        if ($userModel) {
            return $userModel->first_name . ' ' . $userModel->last_name;
        }
        return null;
    }

    public function purchaseRequestItems()
    {
        return $this->hasMany(PurchaseRequestItemModel::class, 'purchase_request_id');
    }
}
