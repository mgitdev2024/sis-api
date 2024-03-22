<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryModel extends Model
{
    use HasFactory;
    protected $table = 'categories';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'category_code',
        'category_name',
        'status'
    ];
}
