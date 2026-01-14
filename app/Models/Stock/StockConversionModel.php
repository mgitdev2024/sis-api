<?php

namespace App\Models\Stock;

use App\Models\Store\StoreReceivingInventoryItemModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockConversionModel extends Model
{
    use HasFactory;
    protected $table = 'stock_conversions';

    protected $appends = [
        'formatted_store_name_label',
        'created_by_name_label',
        'formatted_created_at_label',
    ];
    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'batch_code',
        'item_code',
        'item_description',
        'item_category_name',
        'type',
        'quantity',
        'converted_quantity',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public static function onGenerateReferenceNumber()
    {
        $latestStockConversion = static::orderBy('id', 'desc')->value('id');
        $nextStockConversionId = $latestStockConversion + 1;
        $referenceNumber = 'STC-' . str_pad($nextStockConversionId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

    public function stockConversionItems()
    {
        return $this->hasMany(StockConversionItemModel::class, 'stock_conversion_id');
    }

    public function getFormattedStoreNameLabelAttribute()
    {
        $storeReceivingInventoryModel = StoreReceivingInventoryItemModel::select('store_name')->where('store_code', $this->store_code)
            ->orderBy('id', 'DESC')
            ->first();
        return $storeReceivingInventoryModel ? $storeReceivingInventoryModel->store_name : null;
    }

    public function getCreatedByNameLabelAttribute()
    {
        $userModel = \App\Models\User::where('employee_id', $this->created_by_id)->first();
        if ($userModel) {
            return $userModel->first_name . ' ' . $userModel->last_name;
        }
        return null;
    }

    public function getFormattedCreatedAtLabelAttribute()
    {
        return $this->created_at ? date('Y-m-d h:i A', strtotime($this->created_at)) : null;
    }
}
