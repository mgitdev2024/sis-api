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
            10.1 => 'For Store Distribution',
            10.2 => 'For Disposal',
            10.3 => 'For Intersell',
            10.4 => 'For Complimentary',
            11 => 'Retouched',
            12 => 'Sliced',
            13 => 'Stored',
            14 => 'For Transfer',
            15 => 'Picked',
            15.1 => 'Checked',
            15.2 => 'For Dispatch',
        ];

        return [
            'key' => $index,
            'value' => $labels[$index]
        ];
    }
}
