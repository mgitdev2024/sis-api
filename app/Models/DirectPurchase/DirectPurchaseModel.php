<?php

namespace App\Models\DirectPurchase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class DirectPurchaseModel extends Model
{
    use HasFactory;

    protected $table = 'direct_purchases';

    protected $appends = [
        'status_label',
        'type_label',
        'formatted_direct_purchase_date_label',
        'formatted_expected_delivery_date_label',
        'formatted_created_at_label',
        'formatted_updated_at_label',
        'created_by_name_label',
        'updated_by_name_label',
    ];
    protected $fillable = [
        'reference_number',
        'direct_reference_number', // MG-0800-4382-2331 PO Number
        'type', // 0 = DR, 1 = PO
        'store_code',
        'store_sub_unit_short_name',
        'supplier_code',
        'supplier_name',
        'direct_purchase_date',
        'expected_delivery_date',
        'status',
        'created_at',
        'updated_at',
        'created_by_id',
        'updated_by_id',
    ];

    public static function onGenerateReferenceNumber()
    {
        $latestDirectPurchaseId = static::latest()->value('id');
        $nextDirectPurchaseId = $latestDirectPurchaseId + 1;
        $referenceNumber = 'DP-' . str_pad($nextDirectPurchaseId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

    public function directPurchaseItems()
    {
        return $this->hasMany(DirectPurchaseItemModel::class, 'direct_purchase_id');
    }

    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 0:
                return 'Pending';
            case 1:
                return 'Closed / Complete';
            case 2:
                return 'Cancelled';
            default:
                return 'Unknown Status';
        }
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

    public function getFormattedDirectPurchaseDateLabelAttribute()
    {
        return $this->direct_purchase_date
            ? Carbon::parse($this->direct_purchase_date)->format('F d, Y')
            : null;
    }

    public function getFormattedExpectedDeliveryDateLabelAttribute()
    {
        return $this->expected_delivery_date
            ? Carbon::parse($this->expected_delivery_date)->format('F d, Y')
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

    public function getTypeLabelAttribute()
    {
        switch ($this->type) {
            case 0:
                return 'Delivery Receipt';
            case 1:
                return 'Purchase Order';
            default:
                return 'Unknown Type';
        }
    }
}
