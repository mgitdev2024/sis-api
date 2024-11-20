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
        'type', // 0 = For Disposal, 1 = For Consumption, 2 = For Endorsement
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
        $typeArray = ['For Disposal', 'For Consumption', 'For Endorsement'];
        $type = $typeArray[$this->type];
        return $type;
    }
}
