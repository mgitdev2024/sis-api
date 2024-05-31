<?php

namespace App\Models\History;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionWarehouseLogModel extends Model
{
    use HasFactory;
    protected $table = 'wms_warehouse_logs';
    protected $fillable = [
        'entity_model',
        'reference_model',
        'reference_id',
        'entity_id',
        'item_key',
        'data',
        'action',
        'created_by_id',
        'updated_by_id',
    ];
}
