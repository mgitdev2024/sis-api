<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategoryModel extends Model
{
    use HasFactory;
    protected $table = 'item_categories';
    protected $fillable = [
        'name',
        'code',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
