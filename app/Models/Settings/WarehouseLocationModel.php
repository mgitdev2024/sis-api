<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocationModel extends Model
{
    use HasFactory;
    protected $table = 'warehouse_location';
    protected $fillable = ['name','description','status'];
}
