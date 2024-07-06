<?php

namespace App\Models\MOS\Production;

use App\Models\CredentialModel;
use App\Models\WMS\Settings\ItemMasterData\ItemDeliveryTypeModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOTBModel extends Model
{
    use HasFactory;
    protected $table = 'mos_production_otbs';
    protected $appends = ['production_label', 'item_category_label'];
    protected $fillable = [
        'production_order_id',
        'delivery_type',
        'item_code',
        'requested_quantity',
        'buffer_level',
        'buffer_quantity',
        'expected_chilled_exp_date',
        'expected_frozen_exp_date',
        'plotted_quantity',
        'actual_quantity',
        'created_by_id',
        'updated_by_id',
        'status',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrderModel::class, 'production_order_id');
    }

    public function itemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_code', 'item_code');
    }

    public function deliveryType()
    {
        return $this->belongsTo(ItemDeliveryTypeModel::class, 'delivery_type');
    }
    public function getProductionLabelAttribute()
    {
        $production_label = $this->productionOrder->toArray();
        return isset($production_label) ? $production_label['reference_number'] : 'n/a';
    }

    public function getItemCategoryLabelAttribute()
    {
        $itemCategory = $this->itemMasterData->itemCategory->toArray();
        return isset($itemCategory) ? $itemCategory['name'] : 'n/a';
    }
}
