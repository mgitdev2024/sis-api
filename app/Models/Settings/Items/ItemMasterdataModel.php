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
        'plant_label'
    ];
    protected $fillable = [
        'description',
        'item_code',
        'item_classification_id',
        'item_variant_type_id',
        'uom_id',
        'primary_conversion_id',
        'secondary_conversion_id',
        'shelf_life',
        'plant_id',
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

        return isset ($itemClassification) ? $itemClassification['name'] : 'n/a';
    }

    public function getItemVariantTypeLabelAttribute()
    {
        $itemVariantType = $this->itemVariantType->toArray();

        return isset ($itemVariantType) ? $itemVariantType['name'] : 'n/a';
    }

    public function getUomLabelAttribute()
    {
        $uom = $this->uom->toArray();
        $data = [
            'short_name' => $uom['short_uom'],
            'long_name' => $uom['long_uom'],
        ];
        return isset ($uom) ? $data : 'n/a';
    }

    public function getPrimaryConversionLabelAttribute()
    {
        $primaryConversion = $this->primaryConversion->toArray();
        $data = [
            'short_name' => $primaryConversion['conversion_short_uom'],
            'long_name' => $primaryConversion['conversion_long_uom'],
        ];
        return isset ($primaryConversion) ? $data : 'n/a';
    }

    public function getSecondaryConversionLabelAttribute()
    {
        $secondaryConversion = $this->secondaryConversion->toArray();
        $data = [
            'short_name' => $secondaryConversion['conversion_short_uom'],
            'long_name' => $secondaryConversion['conversion_long_uom'],
        ];
        return isset ($secondaryConversion) ? $data : 'n/a';
    }
    public function getPlantLabelAttribute()
    {
        $plant = $this->plant->toArray();
        $data = [
            'short_name' => $plant['short_name'],
            'long_name' => $plant['long_name'],
        ];
        return isset ($plant) ? $data : 'n/a';
    }
}
