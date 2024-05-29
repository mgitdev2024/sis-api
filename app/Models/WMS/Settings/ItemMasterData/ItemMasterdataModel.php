<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use App\Models\WMS\Settings\ItemMasterData\ItemCategoryModel;
use App\Models\WMS\Settings\ItemMasterData\ItemConversionModel;
use App\Models\WMS\Settings\ItemMasterData\ItemUomModel;
use App\Models\WMS\Settings\ItemMasterData\ItemVariantTypeModel;
use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMasterdataModel extends Model
{
    use HasFactory;
    protected $table = 'wms_item_masterdata';
    protected $appends = [
        'item_category_label',
        'item_variant_type_label',
        'uom_label',
        'primary_conversion_label',
        'secondary_conversion_label',
        'plant_label',
        'consumer_instructions_label',
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
        'consumer_instructions',
        'created_by_id',
        'updated_by_id',
        'status',
    ];



    public function getStockRotationTypeLabelAttribute()
    {
        $stockRotationTypeLabel = array("FIFO", "FEFO");
        return $stockRotationTypeLabel[$this->stock_rotation_type];
    }
    public function getConsumerInstructionsLabelAttribute()
    {
        $consumerInstructionLabel = [
            1 => "KEEP CHILLED",
            2 => "KEEP FROZEN",
            3 => "REHEAT BEFORE SERVING"
        ];

        if (is_null($this->consumer_instructions)) {
            return null;
        }

        $consumerInstructionsArr = json_decode($this->consumer_instructions, true);
        if (is_array($consumerInstructionsArr)) {
            $labels = [
                'refrigerate' => [],
                'reheat' => null
            ];

            foreach ($consumerInstructionsArr as $value) {
                if ($value == 1 || $value == 2) {
                    $labels['refrigerate'] = $consumerInstructionLabel[$value];
                } elseif ($value == 3) {
                    $labels['reheat'] = $consumerInstructionLabel[$value];
                }
            }

            if (empty($labels['refrigerate'])) {
                unset($labels['refrigerate']);
            }

            return $labels;
        }

        return null;
    }

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategoryModel::class, 'item_category_id', 'id');
    }
    public function itemVariantType()
    {
        return $this->belongsTo(ItemVariantTypeModel::class, 'item_variant_type_id', 'id');
    }
    public function uom()
    {
        return $this->belongsTo(ItemUomModel::class, 'uom_id', 'id');
    }
    public function storageType()
    {
        return $this->belongsTo(StorageTypeModel::class, 'storage_type_id', 'id');
    }
    public function primaryConversion()
    {
        return $this->belongsTo(ItemConversionModel::class, 'primary_conversion_id', 'id');
    }
    public function secondaryConversion()
    {
        return $this->belongsTo(ItemConversionModel::class, 'secondary_conversion_id', 'id');
    }
    public function plant()
    {
        return $this->belongsTo(FacilityPlantModel::class, 'plant_id', 'id');
    }
    public function getItemCategoryLabelAttribute()
    {
        $itemCategory = $this->itemCategory->toArray();
        return isset($itemCategory) ? $itemCategory['name'] : 'n/a';
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
            'short_name' => $uom['short_name'],
            'long_name' => $uom['long_name'],
        ];
        return isset($uom) ? $data : 'n/a';
    }

    public function getPrimaryConversionLabelAttribute()
    {
        $primaryConversion = $this->primaryConversion;

        if ($primaryConversion !== null) {
            $data = [
                'short_name' => $primaryConversion->conversion_short_name,
                'long_name' => $primaryConversion->conversion_long_name,
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
                'short_name' => $secondaryConversion->conversion_short_name,
                'long_name' => $secondaryConversion->conversion_long_name,
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
