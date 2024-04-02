<?php

namespace App\Models\Productions;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBatchModel extends Model
{
    use HasFactory;
    protected $appends = ['batch_type_label'];
    protected $table = 'production_batch';
    protected $fillable = [
        'production_otb_id',
        'production_ota_id',
        'produced_item_id',
        'batch_code',
        'batch_number',
        'batch_type',
        'quantity',
        'chilled_exp_date',
        'frozen_exp_date',
        'created_by_id',
        'updated_by_id',
        'status',
    ];

    public function createdBy()
    {
        return $this->belongsTo(CredentialModel::class, 'created_by_id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(CredentialModel::class, 'updated_by_id');
    }

    public function productionOTB()
    {
        return $this->belongsTo(ProductionOTBModel::class, 'production_otb_id');
    }

    public function producedItem()
    {
        return $this->belongsTo(ProducedItemModel::class, 'produced_item_id');
    }

    public function getBatchTypeLabelAttribute()
    {
        $batchType = ['Fresh', 'Reprocessed'];
        return $batchType[$this->batch_type];
    }

    public static function setBatchTypeLabel($index)
    {
        $batchType = ['Fresh', 'Reprocessed'];
        $label = null;
        if ($index) {
            $label = $batchType[$index];
        }

        return $label;
    }

    public static function generateBatchCode($itemCode, $deliveryType, $batchNumber, $isReprocessed = false)
    {
        $itemCode = str_replace(' ', '', $itemCode);
        $monthCode = chr(date('n') + 64);
        $day = date('j');

        $batchCode = $monthCode . $day . '-' . $itemCode . str_pad($batchNumber, 2, '0', STR_PAD_LEFT);

        if ($deliveryType != null || $deliveryType != "") {
            $batchCode .= '-' . $deliveryType;
        }

        if ($isReprocessed) {
            $batchCode .= '-R';
        }

        return $batchCode;
    }
}
