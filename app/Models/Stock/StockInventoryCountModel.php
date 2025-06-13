<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInventoryCountModel extends Model
{
    use HasFactory;

    protected $table = 'stock_inventory_count';

    protected $appends = ['type_label'];
    protected $fillable = [
        'reference_number',
        'type', // 1 = Hourly, 2 = EOD, 3 = Month-End
        'store_code',
        'store_sub_unit_short_name',
        'created_by_id',
        'updated_by_id',
        'status',
    ];

    public function stockInventoryItemsCount()
    {
        return $this->hasMany(StockInventoryItemCountModel::class);
    }

    public static function onGenerateReferenceNumber()
    {
        $latestStoreInventoryCount = static::latest()->value('id');
        $nextStoreInventoryCount = $latestStoreInventoryCount + 1;
        $referenceNumber = 'SC-' . str_pad($nextStoreInventoryCount, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

    public function getTypeLabelAttribute()
    {
        $type = [
            1 => 'Hourly',
            2 => 'End of Day',
            3 => 'Month-End',
        ];
        return $type[$this->type] ?? 'Unknown';
    }
}
