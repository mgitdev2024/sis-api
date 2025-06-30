<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItemModel extends Model
{
    use HasFactory;
    protected $table = 'stock_transfer_items';
    protected $fillable = [
        'stock_transfer_id',
        'item_code',
        'item_description',
        'item_category_name',
        'quantity',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    /**
     * Relationships
     */
    public function stockTransfer()
    {
        return $this->belongsTo(StockTransferModel::class);
    }
}
