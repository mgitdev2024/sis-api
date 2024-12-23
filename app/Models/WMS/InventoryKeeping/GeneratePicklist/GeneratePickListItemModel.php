<?php

namespace App\Models\WMS\InventoryKeeping\GeneratePicklist;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratePickListItemModel extends Model
{
    use HasFactory;

    protected $table = 'wms_generate_picklist_items';

    protected $fillable = [
        'generate_picklist_id',
        'store_id',
        'store_code',
        'store_name',
        'picklist_items',
        'status',
        'created_at',
        'updated_at',
    ];
    public function generatePicklist()
    {
        return $this->belongsTo(GeneratePickListModel::class, 'generate_picklist_id');
    }
}
