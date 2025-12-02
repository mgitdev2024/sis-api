<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreReceivingGoodsIssueItemModel extends Model
{
    use HasFactory;

    protected $table = 'store_receiving_gi_items';

    protected $fillable = [
        'sr_inventory_item_id',
        'gi_id',
        'gi_material_doc_year',
        'gi_material_doc_item',
        'gi_posting_date',
        'gi_inventory_stock_type',
        'gi_inventory_trans_type',
        'gi_batch',
        'gi_shelf_life_exp_date',
        'gi_manu_date',
        'gi_goods_movement_type',
        'gi_purchase_order',
        'gi_purchase_order_item',
        'gi_entry_unit',
        'gi_supplying_plant',
    ];

    /**
     * Relation: GI Item belongs to StoreReceivingInventoryItem (line item)
     */
    public function storeReceivingInventoryItem()
    {
        return $this->belongsTo(StoreReceivingInventoryItemModel::class, 'sr_inventory_item_id', 'id');
    }

    /**
     * Relation: GI Item belongs to GI (header)
     */
    public function goodsIssue()
    {
        return $this->belongsTo(StoreReceivingGoodsIssueModel::class, 'store_receiving_gi_id', 'id');
    }
}
