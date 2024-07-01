<?php

namespace App\Models\MOS\Production;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBatchModel extends Model
{
    use HasFactory;
    protected $appends = ['batch_type_label', 'production_ota_label', 'production_otb_label'];
    protected $table = 'mos_production_batches';
    protected $fillable = [
        'production_otb_id',
        'production_ota_id',
        'production_order_id',
        'production_item_id',
        'batch_code',
        'batch_number',
        'batch_type',
        'quantity',
        'actual_quantity',
        'actual_secondary_quantity',
        'chilled_exp_date',
        'frozen_exp_date',
        'ambient_exp_date',
        'has_endorsement_from_qa',
        'created_by_id',
        'updated_by_id',
        'status', // 0 = In Progress, 1 = On Hold, 2 = Complete
    ];

    public function productionOtb()
    {
        return $this->belongsTo(ProductionOTBModel::class, 'production_otb_id');
    }
    public function productionOta()
    {
        return $this->belongsTo(ProductionOTAModel::class, 'production_ota_id');
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrderModel::class, 'production_order_id');
    }

    public function productionItems()
    {
        return $this->belongsTo(ProductionItemModel::class, 'production_item_id');
    }

    public function getBatchTypeLabelAttribute()
    {
        $batchType = ['Fresh', 'Reprocessed'];
        return $batchType[$this->batch_type];
    }

    public function getStatusLabelAttribute()
    {
        $status = ['In Progress', 'On Hold', 'Complete'/*, 'Complete (Issues)'*/];
        return $status[$this->status];
    }

    public function getProductionOtaLabelAttribute()
    {
        $production_ota_label = $this->productionOta;
        $response = null;
        if (isset($production_ota_label)) {
            $response = [
                'item_code' => $production_ota_label['item_code'],
                'storage_type' => $production_ota_label->itemMasterdata->storageType['name'],
                'plotted_quantity' => $production_ota_label['plotted_quantity'],
                'actual_quantity' => $production_ota_label['actual_quantity'],
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
                'storage_type' => $production_otb_label->itemMasterdata->storageType['name'],
                'plotted_quantity' => $production_otb_label['plotted_quantity'],
                'actual_quantity' => $production_otb_label['actual_quantity'],
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

    public static function generateBatchCode($itemCode, $deliveryType, $batchNumber, $productionDate = null)
    {
        date_default_timezone_set('Asia/Manila');
        $timestamp = $productionDate != null ? strtotime($productionDate) : time();
        $itemCode = str_replace(' ', '', $itemCode);
        $monthCode = chr(date('n', $timestamp) + 64);
        $day = date('j', $timestamp);

        $batchCode = $monthCode . $day . '-' . $itemCode . str_pad($batchNumber, 2, '0', STR_PAD_LEFT);

        if ($deliveryType != null || $deliveryType != "") {
            $batchCode .= '-' . $deliveryType;
        }

        return $batchCode;
    }
}
