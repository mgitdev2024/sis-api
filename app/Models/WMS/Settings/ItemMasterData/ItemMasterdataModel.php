<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use App\Models\WMS\Settings\ItemMasterData\ItemCategoryModel;
use App\Models\WMS\Settings\ItemMasterData\ItemConversionModel;
use App\Models\WMS\Settings\ItemMasterData\ItemUomModel;
use App\Models\WMS\Settings\ItemMasterData\ItemVariantTypeModel;
use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;
use App\Models\WMS\Storage\StockInventoryModel;
use App\Models\WMS\Storage\StockLogModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMasterdataModel extends Model
{
    use HasFactory;
    protected $table = 'wms_item_masterdata';
    protected $appends = [
        'item_classification_label',
        'item_category_label',
        'item_variant_type_label',
        'uom_label',
        'primary_conversion_label',
        'secondary_conversion_label',
        'plant_label',
        'sticker_remarks_label',
        'storage_type_label',
        'stock_type_label',
        // 'stock_rotation_type_label'
    ];
    protected $fillable = [
        'item_code',
        'description',
        'short_name',
        'long_name',
        'parent_item_id',
        'unit_price',
        'item_classification_id',
        'item_category_id',
        'item_variant_type_id',
        'zone_id',
        'ambient_shelf_life',
        'chilled_shelf_life',
        'frozen_shelf_life',
        'uom_id',
        'primary_item_packing_size',
        'primary_conversion_id',
        'secondary_item_packing_size',
        'secondary_conversion_id',
        'storage_type_id',
        'stock_type_id',
        'warehouse_location_id',
        'item_movement_id',
        'delivery_lead_time',
        'inbound_shelf_life',
        'outbound_shelf_life',
        're_order_level',
        'stock_rotation_type',
        'qty_per_pallet',
        'max_qty',
        'dimension_l',
        'dimension_h',
        'dimension_w',
        'item_weight',
        'is_viewable_by_otb',
        'is_qa_required',
        'is_qa_disposal',
        'shelf_life',
        'plant_id',
        'attachment',
        'sticker_remarks_code',
        'created_by_id',
        'updated_by_id',
        'status',
    ];

    public function getStockRotationTypeLabelAttribute()
    {
        $stockRotationTypeLabel = ["FIFO", "FEFO"];
        return $stockRotationTypeLabel[$this->stock_rotation_type];
    }
    public function getStickerRemarksLabelAttribute()
    {
        $stickerRemarksLabel = [
            "STIC-KEEP-CHILLED" => "KEEP CHILLED",
            "STIC-KEEP-FROZEN" => "KEEP FROZEN",
            "STIC-KEEP-CHILLED-OPN" => "KEEP CHILLED ONCE OPENED",
            "STIC-KEEP-COVERED-AMB" => "KEEP COVERED IN AMBIENT STORAGE"
        ];
        return $stickerRemarksLabel[$this->sticker_remarks_code] ?? null;
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
    public function stockInventories()
    {
        return $this->belongsTo(StockInventoryModel::class, 'item_id', 'id');
    }
    public function stockLogs()
    {
        return $this->hasMany(StockLogModel::class, 'item_code', 'item_code');
    }
    public function itemClassification()
    {
        return $this->belongsTo(ItemClassificationModel::class, 'item_classification_id', 'id');

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
    public function getItemClassificationLabelAttribute()
    {
        $itemClassification = $this->itemClassification->toArray();
        $data = [
            'short_name' => $itemClassification['short_name'],
            'long_name' => $itemClassification['long_name'],
        ];
        return isset($itemClassification) ? $data : 'n/a';
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
                'short_name' => $primaryConversion->short_name,
                'long_name' => $primaryConversion->long_name,
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
                'short_name' => $secondaryConversion->short_name,
                'long_name' => $secondaryConversion->long_name,
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

    public function getStorageTypeLabelAttribute()
    {
        $storageType = $this->storageType->toArray();
        $data = [
            'short_name' => $storageType['short_name'],
            'long_name' => $storageType['long_name'],
        ];
        return isset($storageType) ? $data : 'n/a';
    }

    public static function getViewableOtb($itemCode = false)
    {
        $itemMasterdataAdd = ItemMasterdatamodel::query();
        if ($itemCode) {
            $itemMasterdataAdd->select('item_code');
        }
        $itemMasterdataAdd->where('is_viewable_by_otb', 1);
        $itemMasterdata = null;
        if ($itemCode) {
            $itemMasterdata = $itemMasterdataAdd->pluck('item_code');
        } else {
            $itemMasterdata = $itemMasterdataAdd->get();
        }

        if (count($itemMasterdata) > 0) {
            return $itemMasterdata->toArray();
        }
        return null;
    }

    public function stockType()
    {
        return $this->belongsTo(ItemStockTypeModel::class, 'stock_type_id', 'id');
    }

    public function getStockTypeLabelAttribute()
    {
        $stockType = $this->stockType->toArray();
        return $stockType ?? null;
    }
}
