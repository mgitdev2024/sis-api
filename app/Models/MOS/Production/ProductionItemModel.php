<?php

namespace App\Models\MOS\Production;

use App\Models\CredentialModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionItemModel extends Model
{
    use HasFactory;
    protected $table = 'mos_production_items';
    protected $fillable = [
        'production_batch_id',
        'produced_items',
        'production_type',
        'created_by_id',
        'updated_by_id',
        'status',
    ];

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatchModel::class, 'production_batch_id');
    }

    public static function getStatusLabel($index)
    {
        $labels = [
            0 => 'Good',
            1 => 'On Hold',
            "1.1" => 'On Hold - Sub Standard',
            2 => 'For Receive',
            3 => 'Received',
            4 => 'For Investigation',
            5 => 'For Sampling',
            6 => 'For Retouch',
            7 => 'For Slice',
            8 => 'For Sticker Update',
            9 => 'Sticker Updated',
            10 => 'Reviewed',
            11 => 'Retouched',
            12 => 'Sliced'
        ];

        return [
            'key' => $index,
            'value' => $labels[$index]
        ];
    }
}
