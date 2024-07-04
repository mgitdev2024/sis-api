<?php

namespace App\Models\WMS\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehousePutAwayModel extends Model
{
    use HasFactory;
    protected $table = 'wms_warehouse_put_away';

    protected $fillable = [
        'warehouse_receiving_reference_number',
        'reference_number',
        'item_code',
        'production_items',
        'received_quantity',
        'transferred_quantity',
        'substandard_quantity',
        'remaining_quantity',
        'status',
    ];
    public static function onGenerateWarehousePutAwayReferenceNumber($warehouseReceivingReferenceNumber)
    {
        $referenceCount = static::where('warehouse_receiving_reference_number', $warehouseReceivingReferenceNumber)->count();
        return $warehouseReceivingReferenceNumber . '-' . ($referenceCount + 1);
    }
}
