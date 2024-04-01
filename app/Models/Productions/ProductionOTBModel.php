<?php

namespace App\Models\Productions;

use App\Models\CredentialModel;
use App\Models\Settings\Delivery\DeliveryTypeModel;
use App\Models\Settings\Items\ItemMasterdataModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOTBModel extends Model
{
    use HasFactory;
    protected $table = 'production_otb';
    protected $appends = ['production_label', 'item_classification_label'];
    protected $fillable = [
        'production_order_id',
        'delivery_type',
        'item_code',
        'requested_quantity',
        'buffer_level',
        'expected_chilled_exp_date',
        'expected_frozen_exp_date',
        'plotted_quantity',
        'actual_quantity',
        'created_by_id',
        'updated_by_id',
        'status',
    ];

    public function createdBy()
    {
        return $this->belongsTo(CredentialModel::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(CredentialModel::class, 'updated_by_id');
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrderModel::class, 'production_order_id');
    }

    public function itemMasterData()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_code', 'item_code');
    }

    public function deliveryType()
    {
        return $this->belongsTo(DeliveryTypeModel::class, 'delivery_type');
    }
    public function getProductionLabelAttribute()
    {
        $production_label = $this->productionOrder->toArray();
        return isset($production_label) ? $production_label['reference_number'] : 'n/a';
    }

    public function getItemClassificationLabelAttribute()
    {
        $itemClassification = $this->itemMasterData->itemClassification->toArray();
        return isset($itemClassification) ? $itemClassification['name'] : 'n/a';
    }
}
