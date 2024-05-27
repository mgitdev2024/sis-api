<?php

namespace App\Models\WMS\Settings\ItemMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMovement extends Model
{
    use HasFactory;
    protected $table = 'item_movements';
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
