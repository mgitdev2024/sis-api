<?php

namespace App\Models\Sap\GoodReceipt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceiptItemModel extends Model
{
    use HasFactory;

    protected $table = 'sap_goods_receipt_items';

    protected $fillable = [
        'sap_good_receipt_id',
        'plant',
        'material',
        'storage_location',
        'batch',
        'goods_movement_type',
        'purchase_order',
        'purchase_order_item',
        'goods_movement_ref_doc_type',
        'quantity_in_entry_unit',
        'entry_unit',
        'manufacture_date',
        'status',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];

    /**
     * A receipt item belongs to a goods receipt.
     */
    public function goodReceipt()
    {
        return $this->belongsTo(GoodReceiptModel::class, 'sap_good_receipt_id');
    }
}
