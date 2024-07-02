<?php

namespace App\Models\WMS\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehousePutAwayModel extends Model
{
    use HasFactory;
    protected $table = 'wms_warehouse_put_away';

    protected $fillable = [
        'warehouse_receiving_id',
        'warehouse_receiving_reference_number',
        'item_code',
        'production_items',
        'received_quantity',
        'transferred_quantity',
        'substandard_quantity',
        'remaining_quantity',
        'status',
    ];

    public function warehouseReceiving()
    {
        return $this->belongsTo(WarehouseReceivingModel::class, 'warehouse_receiving_id');
    }

}
