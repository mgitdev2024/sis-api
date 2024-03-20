<?php

namespace App\Models\Productions;

use App\Models\Credential;
use App\Models\Delivery\DeliveryType;
use App\Models\Items\ItemMasterdata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOTB extends Model
{
    use HasFactory;
    protected $table = 'production_otb';
    protected $appends = ['production_label'];
    protected $fillable = [
        'production_order_id',
        'delivery_type',
        'item_code',
        'actual_quantity',
        'buffer_level',
        'total_quantity',
        'scanned_quantity',
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

    public function deliveryType()
    {
        return $this->belongsTo(DeliveryType::class, 'delivery_type');
    }
    public function getProductionLabelAttribute()
    {
        $production_label = $this->productionOrder->toArray();
        return isset ($production_label) ? $production_label['reference_number'] : 'n/a';
    }
}
