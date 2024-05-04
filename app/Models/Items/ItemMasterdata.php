<?php

namespace App\Models\Items;

use App\Models\Settings\Items\ItemClassificationModel;
use App\Models\Settings\Items\ItemVariantTypeModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Credential;

class ItemMasterdata extends Model
{
    use HasFactory;
    protected $table = 'item_masterdata';
    protected $appends = ['item_classification_label', 'item_variant_type_label'];
    protected $fillable = [
        'item_code',
        'description',
        'item_classification_id',
        'item_variant_type_id',
        'chilled_shelf_life',
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
        'plant_id',
        'image',
        'created_by_id',
        'updated_by_id',
        'status',
    ];
    /*    public function createdBy()
       {
           return $this->belongsTo(Credential::class, 'created_by_id');
       }
       public function updatedBy()
       {
           return $this->belongsTo(Credential::class, 'updated_by_id');
       }
    */
    public function itemClassification()
    {
        return $this->belongsTo(ItemClassificationModel::class, 'item_classification_id', 'id');
    }
    public function itemVariantType()
    {
        return $this->belongsTo(ItemVariantTypeModel::class, 'item_variant_type_id', 'id');
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
}
