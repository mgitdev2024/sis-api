<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMovementModel extends Model
{
    use HasFactory;
    protected $table = 'item_movement';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'name',
        'description',
        'status'
    ];
}
