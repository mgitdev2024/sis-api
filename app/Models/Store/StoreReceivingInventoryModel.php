<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreReceivingInventoryModel extends Model
{
    use HasFactory;

    protected $table = 'store_receiving_inventory';
    protected $appends = ['status_label'];

    protected $fillable = [
        'consolidated_order_id',
        'warehouse_code',
        'warehouse_name',
        'reference_number',
        'delivery_date',
        'delivery_type',
        'is_sap_created',
        'created_by_name',
        'created_by_id',
        'updated_by_id',
        'status'
    ];

    public function storeReceivingInventoryItems()
    {
        return $this->hasMany(StoreReceivingInventoryItemModel::class, 'store_receiving_inventory_id');
    }

    public static function onGenerateReferenceNumber($consolidatedOrderId)
    {
        $latestStoreReceiving = static::latest()->value('id');
        $nextStoreReceiving = $latestStoreReceiving + 1;
        $referenceNumber = 'C' . $consolidatedOrderId . '-1' . str_pad($nextStoreReceiving, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }

    public function getStatusLabelAttribute()
    {
        return $this->status == 1 ? 'Complete' : 'Pending';
    }


}
