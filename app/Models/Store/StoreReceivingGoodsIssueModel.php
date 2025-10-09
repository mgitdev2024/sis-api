<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreReceivingGoodsIssueModel extends Model
{
    use HasFactory;

    protected $table = 'store_receiving_gi';

    protected $fillable = [
        'sr_inventory_id',
        'gi_posting_date',
        'gi_plant_code',
        'gi_plant_name',
    ];

    /**
     * Relation: GI belongs to StoreReceivingInventory (header)
     */
    public function storeReceivingInventory()
    {
        return $this->belongsTo(StoreReceivingInventoryModel::class, 'sr_inventory_id', 'id');
    }

    /**
     * Relation: GI has many GI items
     */
    public function goodsIssueItems()
    {
        return $this->hasMany(StoreReceivingGoodsIssueItemModel::class, 'store_receiving_gi_id', 'id');
    }
}
