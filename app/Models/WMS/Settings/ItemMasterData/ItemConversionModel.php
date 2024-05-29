<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemConversionModel extends Model
{
    use HasFactory;
    protected $table = 'wms_item_conversions';
    protected $fillable = [
        'code',
        'created_by_id',
        'updated_by_id',
        'short_name',
        'long_name',
        'status',
    ];
}
