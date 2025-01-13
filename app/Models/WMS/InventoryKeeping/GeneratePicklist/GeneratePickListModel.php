<?php

namespace App\Models\WMS\InventoryKeeping\GeneratePicklist;

use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationOrderModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratePickListModel extends Model
{
    use HasFactory;

    protected $table = 'wms_generate_picklists';

    protected $fillable = [
        'reference_number',
        'allocation_order_id',
        'consolidation_reference_number',
    ];
    public function allocationOrder()
    {
        return $this->belongsTo(AllocationOrderModel::class, 'allocation_order_id');
    }

    public static function onGeneratePickListReferenceNumber()
    {
        $latestStockRequest = static::latest()->value('id');
        $nextStockRequest = $latestStockRequest + 1;
        $referenceNumber = 'PL-8' . str_pad($nextStockRequest, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

}
