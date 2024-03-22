<?php

namespace App\Models\Productions;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBatchModel extends Model
{
    use HasFactory;
    protected $table = 'production_batch';
    protected $fillable = [
        'production_otb_id',
        'produced_item_id',
        'batch_code',
        'batch_number',
        'batch_type',
        'quantity',
        'expiration_date',
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

    public static function generateBatchCode($itemCode, $deliveryType, $batchNumber)
    {
        $itemCode = str_replace(' ', '', $itemCode);
        $monthCode = chr(date('n') + 64);
        $day = date('j');

        $batchCode = $monthCode . $day . '-' . $itemCode . str_pad($batchNumber, 2, '0', STR_PAD_LEFT) . '-' . $deliveryType;

        return $batchCode;
    }
}
