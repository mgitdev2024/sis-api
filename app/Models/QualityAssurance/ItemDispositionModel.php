<?php

namespace App\Models\QualityAssurance;

use App\Models\MOS\Production\ProductionBatchModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDispositionModel extends Model
{
    use HasFactory;
    protected $table = 'mos_item_dispositions';
    protected $appends = ['item_variant_label'];
    protected $fillable = [
        'production_batch_id',
        'item_key',
        'production_type', // 0 = otb, 1 = ota
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

    public function getItemVariantLabelAttribute()
    {
        $otbItems = $this->productionBatch->productionOtb->itemMasterdata ?? null;
        $otaItems = $this->productionBatch->productionOta->itemMasterdata ?? null;
        $itemMasterdata = $otbItems ?? $otaItems;
        $itemVariantType = $itemMasterdata->itemVariantType->toArray();
        return isset($itemVariantType) ? $itemVariantType['name'] : null;
    }
}
