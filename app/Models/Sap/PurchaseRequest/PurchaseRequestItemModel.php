<?php

namespace App\Models\Sap\PurchaseRequest;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PurchaseRequestItemModel extends Model
{
    use HasFactory;

    protected $table = "purchase_request_items";

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
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequestModel::class, 'purchase_request_id');
    }
}
