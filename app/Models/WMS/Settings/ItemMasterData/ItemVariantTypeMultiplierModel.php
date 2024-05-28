<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVariantTypeMultiplierModel extends Model
{
    use HasFactory;
    protected $table = 'wms_item_variant_type_multipliers';
    protected $fillable = [
        'item_variant_type_id',
        'multiplier',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
