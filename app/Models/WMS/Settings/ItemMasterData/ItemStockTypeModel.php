<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemStockTypeModel extends Model
{
    use HasFactory;

    protected $table = 'wms_item_stock_types';
    protected $fillable = [
        'code',
        'short_name',
        'long_name',
        'created_by_id',
        'updated_by_id',
        'status',
    ];
}
