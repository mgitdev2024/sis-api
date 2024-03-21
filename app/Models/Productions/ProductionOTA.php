<?php

namespace App\Models\Productions;

use App\Models\Credential;
use App\Models\Items\ItemMasterdata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOTA extends Model
{
    use HasFactory;

    protected $table = 'production_ota';
    protected $appends = ['production_label', 'item_classification_label'];
    protected $fillable = [
        'production_order_id',
        'item_code',
        'requested_quantity',
        'buffer_level',
        'plotted_quantity',
        'actual_quantity',
        'created_by_id',
        'updated_by_id',
        'status',
    ];

    public function createdBy()
    {
        return $this->belongsTo(Credential::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Credential::class, 'updated_by_id');
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function itemMasterData()
    {
        return $this->belongsTo(ItemMasterdata::class, 'item_code', 'item_code');
    }

    public function getProductionLabelAttribute()
    {
        $production_label = $this->productionOrder->toArray();
        return isset ($production_label) ? $production_label['reference_number'] : 'n/a';
    }

    public function getItemClassificationLabelAttribute()
    {
        $itemClassification = $this->itemMasterData->itemClassification->toArray();
        return isset ($itemClassification) ? $itemClassification['name'] : 'n/a';
    }
}
