<?php

namespace App\Models\History;

use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintHistoryModel extends Model
{
    use HasFactory;
    protected $table = 'mos_production_print_histories';
    protected $fillable = [
        'production_batch_id',
        'produced_items',
        'reason',
        'attachment',
        'is_reprint',
        'item_disposition_id',
        'created_by_id',
    ];

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class, 'production_batch_id');
    }

    public function itemDisposition()
    {
        return $this->belongsTo(ItemDispositionModel::class, 'item_disposition_id');
    }
}
