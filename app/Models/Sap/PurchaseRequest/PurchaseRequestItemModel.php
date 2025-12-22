<?php

namespace App\Models\Sap\PurchaseRequest;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class PurchaseRequestItemModel extends Model
{
    use HasFactory;

    protected $table = "purchase_request_items";
    protected $appends = [
        'formatted_created_at_label',
        'formatted_updated_at_label',
        'created_by_name_label',
        'updated_by_name_label',
    ];
    protected $fillable = [
        'purchase_request_id',
        'item_code',
        'item_name',
        'item_category_code',
        'uom',
        'purchasing_organization',
        'purchasing_group',
        'requested_quantity',
        'price', //* Default 1 Peso
        'currency', //* PHP
        'delivery_date', //* Expected Delivery Date
        'remarks', //* Remarks per item
        'created_at',
        'created_by_id',
        'updated_by_id',
        'updated_at'
    ];

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
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequestModel::class, 'purchase_request_id');
    }
}
