<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMovementModel extends Model
{
    use HasFactory;
    protected $table = 'wms_item_movements';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'code',
        'short_name',
        'long_name',
        'description',
        'status'
    ];
}
