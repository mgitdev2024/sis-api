<?php

namespace App\Models\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Credential;

class ItemMasterdata extends Model
{
    use HasFactory;
    protected $table = 'item_masterdata';
    protected $appends = ['item_classification_label', 'item_variant_type_label'];
    protected $fillable = [
        'description',
        'item_code',
        'item_classification_id',
        'item_variant_type_id',
        'shelf_life',
        'plant_id',
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

    public function itemClassification()
    {
        return $this->belongsTo(ItemClassification::class, 'item_classification_id', 'id');
    }

    public function itemVariantType()
    {
        return $this->belongsTo(ItemVariantType::class, 'item_variant_type_id', 'id');
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
}
