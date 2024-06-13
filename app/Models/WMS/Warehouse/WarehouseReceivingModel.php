<?php

namespace App\Models\WMS\Warehouse;

use App\Models\MOS\Production\ProductionOrderModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseReceivingModel extends Model
{

    use HasFactory;
    protected $table = 'wms_warehouse_receiving';
    protected $fillable = [
        'reference_number',
        'production_order_id',
        'batch_number',
        'item_code',
        'produced_items',
        'quantity',
        'received_quantity',
        'substandard_quantity' .
        'sku_type',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrderModel::class);
    }

    public static function onGenerateWarehouseReceiveReferenceNumber()
    {
        $latestWarehouseReceive = static::latest()->value('id');
        $nextWarehouseReceive = $latestWarehouseReceive + 1;
        $referenceNumber = '8' . str_pad($nextWarehouseReceive, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }
}
