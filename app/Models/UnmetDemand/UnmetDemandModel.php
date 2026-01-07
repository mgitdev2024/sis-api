<?php

namespace App\Models\UnmetDemand;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnmetDemandModel extends Model
{
    use HasFactory;
    protected $table = 'unmet_demands';

    protected $fillable = [
        'reference_code',
        'store_code',
        'store_sub_unit_short_name',
        'status',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];

    /**
     * One unmet demand has many items
     */
    public function unmetDemandItems()
    {
        return $this->hasMany(UnmetDemandItemModel::class, 'unmet_demand_id');
    }

    public static function onGenerateReferenceNumber()
    {
        $latestUnmetDemandId = static::orderBy('id', 'desc')->first()->id;
        $nextUnmetDemandId = $latestUnmetDemandId + 1;
        $referenceNumber = 'UN-' . str_pad($nextUnmetDemandId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

}
