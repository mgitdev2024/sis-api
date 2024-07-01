<?php

namespace App\Models\WMS\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseForReceiveModel extends Model
{
    use HasFactory;

    protected $table = 'wms_warehouse_for_receive';
    protected $fillable = [
        'reference_number',
        'production_items',
        'status',
        'created_by_id',
        'updated_by_id',
    ];
    public function warehouseReceiving()
    {
        return $this->belongsTo(WarehouseReceivingModel::class, 'reference_number', 'reference_number');
    }
}
