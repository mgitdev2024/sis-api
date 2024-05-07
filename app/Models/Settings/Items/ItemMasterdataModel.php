<?php

namespace App\Models\Settings\Items;

use App\Models\Settings\Facility\PlantModel;
use App\Models\Settings\Measurements\ConversionModel;
use App\Models\Settings\Measurements\UomModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CredentialModel;

class ItemMasterdataModel extends Model
{
    use HasFactory;
    protected $table = 'item_masterdata';
    protected $appends = [
        'item_classification_label',
        'item_variant_type_label',
        'uom_label',
        'primary_conversion_label',
        'secondary_conversion_label',
        'plant_label',
        // 'stock_rotation_type_label'
    ];
    protected $fillable = [
        'item_code',
        'description',
        'parent_item_id',
        'item_code',
        'item_classification_id',
        'item_variant_type_id',
        'chilled_shelf_life',
        'frozen_shelf_life',
        'category_id',
        'sub_category_id',
        'uom_id',
        'primary_item_packing_size',
        'primary_conversion_id',
        'secondary_item_packing_size',
        'secondary_conversion_id',
        'storage_type_id',
        'stock_type_id',
        'item_movement_id',
        'delivery_lead_time',
        're_order_level',
        'stock_rotation_type',
        'qty_per_pallet',
        'dimension',
        'is_qa_required',
        'is_qa_disposal',
        'shelf_life',
        'plant_id',
        'image',
        'created_by_id',
        'updated_by_id',
        'status',
    ];



    public function getStockRotationTypeLabelAttribute()
    {
        $stockRotationTypeLabel = array("FIFO", "FEFO");
        return $stockRotationTypeLabel[$this->stock_rotation_type];
    }
    public function itemClassification()
    {
        return $this->belongsTo(ItemClassificationModel::class, 'item_classification_id', 'id');
    }
    public function itemVariantType()
    {
        return $this->belongsTo(ItemVariantTypeModel::class, 'item_variant_type_id', 'id');
    }
    public function uom()
    {
        return $this->belongsTo(UomModel::class, 'uom_id', 'id');
    }
    public function primaryConversion()
    {
        return $this->belongsTo(ConversionModel::class, 'primary_conversion_id', 'id');
    }
    public function secondaryConversion()
    {
        return $this->belongsTo(ConversionModel::class, 'secondary_conversion_id', 'id');
    }
    public function plant()
    {
        return $this->belongsTo(PlantModel::class, 'plant_id', 'id');
    }
    public function getItemClassificationLabelAttribute()
    {
        $itemClassification = $this->itemClassification->toArray();
        return isset($itemClassification) ? $itemClassification['name'] : 'n/a';
    }

    public function getItemVariantTypeLabelAttribute()
    {
        $itemVariantType = $this->itemVariantType->toArray();
        return isset($itemVariantType) ? $itemVariantType['name'] : 'n/a';
    }

    public function getUomLabelAttribute()
    {
        $uom = $this->uom->toArray();
        $data = [
            'short_name' => $uom['short_uom'],
            'long_name' => $uom['long_uom'],
        ];
        return isset($uom) ? $data : 'n/a';
    }

    public function getPrimaryConversionLabelAttribute()
    {
        $primaryConversion = $this->primaryConversion;

        if ($primaryConversion !== null) {
            $data = [
                'short_name' => $primaryConversion->conversion_short_uom,
                'long_name' => $primaryConversion->conversion_long_uom,
            ];
            return $data;
        } else {
            return 'n/a';
        }
    }

    public function getSecondaryConversionLabelAttribute()
    {
        $secondaryConversion = $this->secondaryConversion;

        if ($secondaryConversion !== null) {
            $data = [
                'short_name' => $secondaryConversion->conversion_short_uom,
                'long_name' => $secondaryConversion->conversion_long_uom,
            ];
            return $data;
        } else {
            return 'n/a';
        }
    }
    public function getPlantLabelAttribute()
    {
        $plant = $this->plant->toArray();
        $data = [
            'short_name' => $plant['short_name'],
            'long_name' => $plant['long_name'],
        ];
        return isset($plant) ? $data : 'n/a';
    }
}
