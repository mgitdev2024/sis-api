<?php

namespace App\Models\Admin\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLogModel extends Model
{
    use HasFactory;
    protected $table = 'admin_system_logs';
    protected $fillable = [
        'entity_id',
        'entity_model',
        'data',
        'action',
        'created_by_id',
        'updated_by_id',
    ];
}
