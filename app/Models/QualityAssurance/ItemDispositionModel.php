<?php

namespace App\Models\QualityAssurance;

use App\Models\Productions\ProductionBatchModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDispositionModel extends Model
{
    use HasFactory;
    protected $table = 'item_dispositions';

    protected $fillable = [
        'production_batch_id',
        'item_key',
        'production_type',
        'type', // 0 = for investigation, 1 = for sampling
        'produced_items',
        'reason',
        'attachment',
        'status',
        'production_status',
        'action',
        'aging_period',
        'created_by_id',
        'updated_by_id'
    ];
    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class);
    }
}
