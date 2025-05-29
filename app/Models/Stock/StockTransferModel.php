<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferModel extends Model
{
    use HasFactory;
    protected $table = 'stock_transfers';

    protected $appends = ['transfer_type_label', 'transportation_type_label', 'status_label'];
    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'transfer_type',  // 0 = Store Transfer, 1 = Pull Out
        'transportation_type', // 1: Logistics, 2: Third Party
        'logistics_picked_up_at',
        'logistics_confirmed_by_id',
        'warehouse_received_by_name',
        'pickup_date',
        'location_code',
        'location_name',
        'location_sub_unit',
        'remarks',
        'attachment',
        'created_by_id',
        'updated_by_id',
        'status' // 0 = Cancelled, 1 = For Receive, 1.1 = In warehouse, 2 = Received
    ];

    /**
     * Relationships
     */
    public function StockTransferItems()
    {
        return $this->hasMany(StockTransferItemModel::class, 'stock_transfer_id', 'id');
    }

    public static function onGenerateReferenceNumber($type)
    {
        $prefix = match (strtolower($type)) {
            'pullout' => 'PT-',
            'store' => 'ST-',
            'store_warehouse_store' => 'SWS-',
        };

        $latestReference = static::where('reference_number', 'like', $prefix . '%')
            ->orderBy('reference_number', 'desc')
            ->value('reference_number');

        if ($latestReference) {
            $lastNumber = (int) str_replace($prefix, '', $latestReference);
        } else {
            $lastNumber = 6000000;
        }

        $nextNumber = $lastNumber + 1;

        // Build new reference number
        return $prefix . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
    }

    public function getTransferTypeLabelAttribute()
    {
        $transferType = [
            0 => 'Store',
            1 => 'Pullout',
            2 => 'Store Warehouse Store',
        ];
        return $transferType[$this->transfer_type] ?? 'Unknown';
    }
    public function getTransportationTypeLabelAttribute()
    {
        $transportationTypeArr = [
            1 => 'Logistics',
            2 => 'Third Party',
        ];
        return $transportationTypeArr[$this->transportation_type] ?? null;
    }
    public function getStatusLabelAttribute()
    {
        $statusArr = [
            0 => 'Cancelled',
            1 => 'For Receive',
            1.1 => 'In Warehouse',
            2 => 'Received',
        ];
        return $statusArr[$this->status] ?? 'Unknown';
    }
}
