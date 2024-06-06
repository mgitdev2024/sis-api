<?php

namespace App\Models\QualityAssurance;

use App\Models\MOS\Production\ProductionBatchModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubStandardItemModel extends Model
{
    use HasFactory;

    protected $table = 'qa_sub_standard_items';
    protected $appends = ['production_batch_label'];
    protected $fillable = [
        'reason',
        'attachment',
        'production_batch_id',
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
}
