<?php

namespace App\Models\QualityAssurance;

use App\Models\MOS\Production\ProductionBatchModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubStandardItemModel extends Model
{
    use HasFactory;

    protected $table = 'qa_sub_standard_items';
    protected $appends = ['production_batch_label', 'location_label'];
    protected $fillable = [
        'reason',
        'attachment',
        'location_id',
        'production_batch_id',
        'item_code',
        'item_key',
        'production_type',
        'item_disposition_id',
        'created_by_id',
        'updated_by_id'
    ];

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class);
    }

    public function getProductionBatchLabelAttribute()
    {
        return $this->productionBatch ? $this->productionBatch->batch_number : null;
    }

    public function getLocationLabelAttribute()
    {
        $locationArray = [
            1 => 'Breads - Metal Line',
            2 => 'Cakes - Metal Line',
            3 => 'Warehouse - FG Receiving',
            4 => 'Warehouse - FG Transfer',
            5 => 'Warehouse - FG Dispatch',
        ];

        return $locationArray[$this->location_id] ?? null;
    }
}
