<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInventoryCountTemplateModel extends Model
{
    use HasFactory;

    protected $table = 'stock_inventory_count_template';

    protected $fillable = [
        'store_code',
        'store_sub_unit_short_name',
        'selection_template',
        'status',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];
}
