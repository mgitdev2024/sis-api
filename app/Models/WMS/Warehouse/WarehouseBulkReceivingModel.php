<?php

namespace App\Models\WMS\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseBulkReceivingModel extends Model
{
    use HasFactory;
    protected $table = 'wms_warehouse_receiving_bulk';

    protected $fillable = [
        'bulk_transaction_number',
        'reference_number',
        'production_items',
        'status',
        'created_by_id',
    ];

    public static function getNextTransactionNumber()
    {
        $last = self::orderBy('bulk_transaction_number', 'DESC')->first();
        if ($last) {
            return $last->bulk_transaction_number + 1;
        }
        return 1;
    }
}
