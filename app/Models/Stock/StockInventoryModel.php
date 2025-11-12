<?php

namespace App\Models\Stock;

use App\Models\Store\StoreReceivingInventoryItemModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInventoryModel extends Model
{
    use HasFactory;

    protected $table = 'stock_inventories';

    protected $appends = [
        'formatted_store_name_label',
    ];
    protected $fillable = [
        'store_code',
        'store_sub_unit_short_name',
        'item_code',
        'item_description',
        'item_category_name',
        'stock_count',
        'is_base_unit',
        'uom',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function getFormattedStoreNameLabelAttribute()
    {
        $storeReceivingInventoryModel = StoreReceivingInventoryItemModel::select('store_name')->where('store_code', $this->store_code)
            ->orderBy('id', 'DESC')
            ->first();
        return $storeReceivingInventoryModel ? $storeReceivingInventoryModel->store_name : null;
    }
}
