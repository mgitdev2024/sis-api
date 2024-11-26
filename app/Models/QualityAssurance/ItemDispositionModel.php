<?php

namespace App\Models\QualityAssurance;

use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDispositionModel extends Model
{
    use HasFactory;
    protected $table = 'qa_item_dispositions';
    protected $appends = ['item_variant_label', 'is_sliceable_label'];
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
        'fulfilled_batch_id',
        'created_by_id',
        'updated_by_id'
    ];
    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class, 'production_batch_id');
    }

    public function fulfilledBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class, 'fulfilled_batch_id');
    }

    public function getItemVariantLabelAttribute()
    {
        if ($this->production_batch_id != null) {
            $otbItems = $this->productionBatch->productionOtb->itemMasterdata ?? null;
            $otaItems = $this->productionBatch->productionOta->itemMasterdata ?? null;
            $itemMasterdata = $otbItems ?? $otaItems;
            $itemVariantType = $itemMasterdata->itemVariantType->toArray();
            return isset($itemVariantType) ? $itemVariantType['name'] : null;
        }
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

    public function getIsSliceableLabelAttribute()
    {
        if ($this) {
            $baseCode = explode(' ', $this->item_code)[0];
            $parentItemCollection = ItemMasterdataModel::where('item_code', 'like', $baseCode . '%')
                ->whereNotNull('parent_item_id')
                ->where('item_variant_type_id', 3)->first();
            $isSliceable = false;
            if ($parentItemCollection) {
                $parentIds = json_decode($parentItemCollection->parent_item_id, true);
                if (in_array($this->id, $parentIds)) {
                    $isSliceable = true;
                }
            }
            return $isSliceable;
        }
    }
}
