<?php

namespace App\Models\WMS\Warehouse;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehousePutAwayModel extends Model
{
    use HasFactory;
    protected $table = 'wms_warehouse_put_away';

    protected $fillable = [
        'warehouse_receiving_reference_number',
        'reference_number',
        'item_id',
        'production_items',
        'transfer_items',
        'received_quantity',
        'transferred_quantity',
        'substandard_quantity',
        'remaining_quantity',
        'temporary_storage_id',
        'status', // 0 = pending, 1 = complete
    ];
    public static function onGenerateWarehousePutAwayReferenceNumber($warehouseReceivingReferenceNumber)
    {
        $referenceCount = static::where('warehouse_receiving_reference_number', $warehouseReceivingReferenceNumber)->count();
        return $warehouseReceivingReferenceNumber . '-' . ($referenceCount + 1);
    }

    public function itemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_id');
    }

    public function queuedTemporaryStorage()
    {
        return $this->belongsTo(QueuedTemporaryStorageModel::class, 'temporary_storage_id');
    }

    public function subLocation()
    {
        return $this->belongsTo(SubLocationModel::class, 'temporary_storage_id');
    }
}
