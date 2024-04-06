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
        'created_by_id',
        'updated_by_id',
        'production_batch_id',
        'type',
        'produced_items',
        'reason',
        'attachment',
        'status'
    ];
    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class);
    }
}
