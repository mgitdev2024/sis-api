<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    use HasFactory;
    protected $table = 'item_categories';
    protected $fillable = [
        'code',
        'name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
