<?php

namespace App\Models\Admin\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSystemModel extends Model
{
    use HasFactory;
    protected $table = 'admin_systems';
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'created_by_id',
        'updated_by_id',
    ];
}
