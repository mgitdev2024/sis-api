<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageTypeModel extends Model
{
    use HasFactory;
    protected $table = 'storage_type';
    protected $fillable = ['name','description','status'];
}
