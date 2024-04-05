<?php

namespace App\Models\Productions;

use App\Models\CredentialModel;
use App\Models\Productions\ProductionBatchModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProducedItemModel extends Model
{
    use HasFactory;
    protected $table = 'produced_items';
    protected $fillable = [
        'production_batch_id',
        'produced_items',
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

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class, 'production_batch_id');
    }

    public static function getStatusLabel($index)
    {
        $labels = [
            1 => 'Good',
            2 => 'For Investigation',
            3 => 'For Sampling',
            4 => 'For Disposal',
            5 => 'On Hold',
            6 => 'For Receive',
            7 => 'Received'
        ];

        return [
            'key' => $index,
            'value' => $labels[$index]
        ];
    }
}
