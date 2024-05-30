<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVariantTypeModel extends Model
{
    use HasFactory;

    // protected $appends = ['sticker_multiplier_label'];
    protected $table = 'wms_item_variant_types';
    protected $fillable = [
        'code',
        'short_name',
        'name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    public function stickerMultiplier()
    {
        return $this->belongsTo(ItemVariantTypeMultiplierModel::class, 'id', 'item_variant_type_id');
    }

    // public function getStickerMultiplierLabelAttribute()
    // {
    //     return $this->stickerMultiplier ? $this->stickerMultiplier->multiplier : null;
    // }
}
