<?php

namespace App\Models\Settings\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemClassificationModel extends Model
{
    use HasFactory;
    protected $table = 'item_classifications';
    protected $fillable = [
        'code',
        'short_name',
        'long_name',
        'description',
        'created_by_id',
        'updated_by_id',
        'status'
    ];
}
