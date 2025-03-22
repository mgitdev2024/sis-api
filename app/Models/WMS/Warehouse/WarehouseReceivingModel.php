<?php

namespace App\Models\WMS\Warehouse;

use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionOrderModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseReceivingModel extends Model
{

    use HasFactory;
    protected $table = 'wms_warehouse_receiving';
    protected $fillable = [
        'reference_number',
        'production_order_id',
        'production_batch_id',
        'batch_number',
        'item_code',
        'temporary_storage_id',
        'produced_items',
        'quantity',
        'received_quantity',
        'substandard_quantity' .
        'sku_type',
        'status', // 0 = pending, 1 = completed
        'created_by_id',
        'updated_by_id',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrderModel::class);
    }
    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class);
    }
    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'temporary_storage_id', 'id');
    }

    public static function onGenerateWarehouseReceiveReferenceNumber()
    {
        $latestWarehouseReceive = static::orderBy('reference_number', 'DESC')->value('reference_number');

        if (!$latestWarehouseReceive) {
            $nextWarehouseReceive = 1;
        } else {
            $latestNumber = intval(substr($latestWarehouseReceive, 1));
            $nextWarehouseReceive = $latestNumber + 1;
        }
        $referenceNumber = '8' . str_pad($nextWarehouseReceive, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }
}
