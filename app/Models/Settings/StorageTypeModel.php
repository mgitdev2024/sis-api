<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageTypeModel extends Model
{
    use HasFactory;
    protected $table = 'storage_type';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'name',
        'description',
        'status'
    ];
}
