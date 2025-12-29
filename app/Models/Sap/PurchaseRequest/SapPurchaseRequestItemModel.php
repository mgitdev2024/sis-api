<?php

namespace App\Models\Sap\PurchaseRequest;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapPurchaseRequestItemModel extends Model
{
    use HasFactory;
    protected $table = "sap_purchase_request_items";

    protected $fillable = [
        'purchase_request_id',
        'purchase_requisition_item',
        'material',
        'material_group',
        'plant',
        'company_code',
        'purchasing_organization',
        'purchasing_group',
        'requested_quantity',
        'purchase_requisition_price', //* Default 1 Peso
        'purchase_requisition_item_currency', //* PHP
        'delivery_date', //* Expected Delivery Date
        'storage_location',
        'purchase_requisition_item_text', //* Remarks per item
        'created_at',
        'created_by_id',
        'updated_by_id',
        'updated_at'
    ];

    public function sapPurchaseRequest()
    {
        return $this->belongsTo(SapPurchaseRequestModel::class, 'purchase_request_id');
    }
}
