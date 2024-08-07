<?php

namespace App\Models\Admin\Asset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetListModel extends Model
{
    use HasFactory;
    protected $table = 'admin_asset_lists';
    protected $fillable = [
        'created_by_id',
        'file',
        'keyword',
        'original_file_name',
        'altered_file_name',
        'file_path',
    ];
}
