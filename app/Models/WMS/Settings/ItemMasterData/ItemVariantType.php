<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVariantType extends Model
{
    use HasFactory;
    protected $table = 'item_variant_types';
    protected $fillable = [
        'code',
        'short_name',
        'name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
