<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScmSystemModel extends Model
{
    use HasFactory;
    protected $table = 'scm_systems';
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'created_by_id',
        'updated_by_id',
    ];
}
