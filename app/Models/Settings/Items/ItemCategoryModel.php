<?php

namespace App\Models\Settings\Items;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategoryModel extends Model
{
    use HasFactory;

    protected $table = 'item_category';
    protected $fillable = [
        'name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];



}
