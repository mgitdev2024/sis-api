<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDeliveryType extends Model
{
    use HasFactory;
    protected $table = 'item_delivery_types';
    protected $fillable = [
        'code',
        'name',
        'description',
        'created_by_id',
        'updated_by_id',
        'status',
    ];
    
}
