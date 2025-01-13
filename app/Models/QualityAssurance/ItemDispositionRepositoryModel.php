<?php

namespace App\Models\QualityAssurance;

use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDispositionRepositoryModel extends Model
{
    use HasFactory;

    protected $table = 'qa_item_disposition_repositories';
    protected $appends = ['type_label'];
    protected $fillable = [
        'type', // 0 = For Disposal, 1 = For Intersell, 2 = For Store Distribution, 3 = For Complimentary
        'production_batch_id',
        'item_id',
        'quantity',
        'status',
        'created_at',
        'updated_at',
    ];
    public function itemMasterdata()
    {
        return $this->belongsTo(ItemMasterdataModel::class, 'item_id');
    }
    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class, 'production_batch_id');
    }
    public function getTypeLabelAttribute()
    {
        if ($this->type !== null) {
            $typeArray = ['For Disposal', 'For Intersell', 'For Store Distribution', 'For Complimentary'];
            $type = $typeArray[$this->type];
            return $type;
        }

    }
}
