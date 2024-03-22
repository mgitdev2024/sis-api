<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMovementModel extends Model
{
    use HasFactory;
    protected $table = 'item_movement';
    protected $fillable = ['name','description','status'];
}
