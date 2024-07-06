<?php

namespace App\Models\QualityAssurance;

use App\Models\MOS\Production\ProductionBatchModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDispositionModel extends Model
{
    use HasFactory;
    protected $table = 'qa_item_dispositions';
    protected $appends = ['item_variant_label'];
    protected $fillable = [
        'production_batch_id',
        'item_code',
        'item_key',
        'production_type', // 0 = otb, 1 = ota
        'type', // 0 = for investigation, 1 = for sampling
        'produced_items',
        'reason',
        'attachment',
        'status', //  0 = closed , 1 = open
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

    public static function getStatusLabel($index)
    {
        $labels = [
            0 => 'Good',
            1 => 'On Hold',
            "1.1" => 'On Hold - Sub Standard',
            2 => 'For Receive',
            "2.1" => 'For Receive - In Process',
            3 => 'Received',
            "3.1" => 'For Put-away - In Process',
            4 => 'For Investigation',
            5 => 'For Sampling',
            6 => 'For Retouch',
            7 => 'For Slice',
            8 => 'For Sticker Update',
            9 => 'Sticker Updated',
            10 => 'Reviewed',
            11 => 'Retouched',
            12 => 'Sliced',
            13 => 'Stored'
        ];

        return [
            'key' => $index,
            'value' => $labels[$index]
        ];
    }
}
