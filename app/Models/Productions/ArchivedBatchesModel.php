<?php

namespace App\Models\Productions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivedBatchesModel extends Model
{
    use HasFactory;
    protected $table = 'archived_batches';
    protected $fillable = [
        'production_order_id',
        'batch_number',
        'production_type',
        'production_batch_data',
        'production_items_data',
        'reason',
        'attachment',
        'status',
        'created_by_id',
        'updated_by_id',
        'approved_by_id',
        'approved_at',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrderModel::class);
    }

}
