<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreConsolidationCacheModel extends Model
{
    use HasFactory;

    protected $table = 'store_consolidation_cache';

    protected $fillable = [
        'created_by_id',
        'created_by_name',
        'consolidated_data',
        'status',
        'consolidated_order_id',
    ];
}
