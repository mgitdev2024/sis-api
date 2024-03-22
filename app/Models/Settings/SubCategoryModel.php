<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategoryModel extends Model
{
    use HasFactory;
    protected $table = 'sub_categories';
    protected $fillable = ['category_id', 'sub_category_code', 'sub_category_name','status'];

    protected $appends = ['category_label'];

    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category_id'); 
    }
    public function getCategoryLabelAttribute()
    {
        return $this->category->category;
    }
}
