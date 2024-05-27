<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemUom extends Model
{
    use HasFactory;
    protected $table = 'item_uom';
    protected $fillable = [
        'code',
        'created_by_id',
        'updated_by_id',
        'short_name',
        'long_name',
        'status',
    ];

}
