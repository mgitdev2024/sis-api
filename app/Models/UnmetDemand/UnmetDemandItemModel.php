<?php

namespace App\Models\UnmetDemand;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnmetDemandItemModel extends Model
{
    use HasFactory;

    protected $table = 'unmet_demand_items';

    protected $fillable = [
        'unmet_demand_id',
        'item_code',
        'item_description',
        'item_category_name',
        'quantity',
        'status',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Each item belongs to an unmet demand
     */
    public function unmetDemand()
    {
        return $this->belongsTo(UnmetDemandModel::class, 'unmet_demand_id');
    }
}
