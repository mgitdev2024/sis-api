<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ZoneModel extends Model
{
    use HasFactory;
    
    protected $table = 'zone';
    protected $fillable = ['name','description','status'];
}
