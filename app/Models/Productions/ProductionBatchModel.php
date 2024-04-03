<?php

namespace App\Models\Productions;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBatchModel extends Model
{
    use HasFactory;
    protected $appends = ['batch_type_label', 'status_label', 'production_ota_label', 'production_otb_label'];
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

    public function productionOtb()
    {
        return $this->belongsTo(ProductionOTBModel::class, 'production_otb_id');
    }
    public function productionOta()
    {
        return $this->belongsTo(ProductionOTAModel::class, 'production_ota_id');
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

    public function getStatusLabelAttribute()
    {
        $status = ['In Progress', 'On Hold', 'Complete', 'Complete (Issues)'];
        return $status[$this->status];
    }

    public function getProductionOtaLabelAttribute()
    {
        $production_ota_label = $this->productionOta;
        $response = null;
        if (isset($production_ota_label)) {
            $response = [
                'item_code' => $production_ota_label['item_code'],
                'plotted_quantity' => $production_ota_label['plotted_quantity'],
                'actual_quantity' => $production_ota_label['plotted_quantity'],
            ];
        }
        return $response;
    }

    public function getProductionOtbLabelAttribute()
    {
        $production_otb_label = $this->productionOtb;
        $response = null;
        if (isset($production_otb_label)) {
            $response = [
                'item_code' => $production_otb_label['item_code'],
                'plotted_quantity' => $production_otb_label['plotted_quantity'],
                'actual_quantity' => $production_otb_label['plotted_quantity'],
            ];
        }
        return $response;
    }

    public static function setBatchTypeLabel($index)
    {
        $batchType = ['Fresh', 'Reprocessed'];
        $label = null;
        if ($index !== "" || $index !== null) {
            $label = $batchType[$index];
        }

        return $label;
    }

    public static function generateBatchCode($itemCode, $deliveryType, $batchNumber)
    {
        $itemCode = str_replace(' ', '', $itemCode);
        $monthCode = chr(date('n') + 64);
        $day = date('j');

        $batchCode = $monthCode . $day . '-' . $itemCode . str_pad($batchNumber, 2, '0', STR_PAD_LEFT);

        if ($deliveryType != null || $deliveryType != "") {
            $batchCode .= '-' . $deliveryType;
        }

        return $batchCode;
    }
}
