<?php

namespace App\Models\Sap\GoodReceipt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodReceiptModel extends Model
{
    use HasFactory;

    protected $table = 'sap_goods_receipts';

    protected $fillable = [
        'definition_id',
        'bpa_response_id',
        'goods_movement_code',
        'posting_date',
        'document_date',
        'material_document_header_text',
        'reference_document',
        'error_message',
        'upload_status',
        'status',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Relationships
     */
    public function goodReceiptItems()
    {
        return $this->hasMany(GoodReceiptItemModel::class, 'sap_good_receipt_id');
    }
}
