<?php

namespace App\Models\Stock;

use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferModel extends Model
{
    use HasFactory;
    protected $table = 'stock_transfers';

    protected $appends = [
        'transfer_type_label',
        'transportation_type_label',
        'status_label',
        'created_by_name_label',
        'formatted_store_name_label',
        'formatted_store_received_at_label',
        'formatted_store_received_by_label',
        'formatted_warehouse_received_at_label',
        'formatted_created_at_report_label',
        'formatted_logistics_picked_up_at_report_label',
    ];
    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'transfer_type',  // 0 = Store Transfer, 1 = Pull Out, 2 = store warehouse store
        'transportation_type', // 1: Logistics, 2: Third Party
        'store_received_at',
        'store_received_by_id',
        'logistics_picked_up_at',
        'logistics_confirmed_by_id',
        'warehouse_received_by_name',
        'warehouse_received_at',
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
    public function stockTransferItems()
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
            3 => 'Store Staff',
        ];
        return $transportationTypeArr[$this->transportation_type] ?? null;
    }
    public function getStatusLabelAttribute()
    {
        $statusArr = [
            0 => 'Cancelled',
            1 => 'For Pickup',
            '1.1' => 'In Transit',
            '1.2' => 'For Store Receive',
            2 => 'Received',
        ];
        return $statusArr[(string) $this->status] ?? 'Unknown';
    }

    public function getFormattedStoreReceivedAtLabelAttribute()
    {
        return $this->store_received_at ? date('F j, Y h:i A', strtotime($this->store_received_at)) : null;
    }

    public function getFormattedStoreReceivedByLabelAttribute()
    {
        $userModel = User::where('employee_id', $this->store_received_by_id)->first();

        return $userModel ? "$userModel->first_name $userModel->last_name" : null;
    }

    public function getFormattedWarehouseReceivedAtLabelAttribute()
    {
        return $this->warehouse_received_at ? date('F j, Y h:i A', strtotime($this->warehouse_received_at)) : null;
    }

    public function getFormattedCreatedAtReportLabelAttribute()
    {
        return $this->created_at ? date('Y-m-d h:i A', strtotime($this->created_at)) : null;
    }

    public function getCreatedByNameLabelAttribute()
    {
        $userModel = User::where('employee_id', $this->created_by_id)->first();

        return $userModel ? "$userModel->first_name $userModel->last_name" : null;
    }

    public function getFormattedStoreNameLabelAttribute()
    {
        $storeReceivingInventoryModel = StoreReceivingInventoryItemModel::select('store_name')->where('store_code', $this->store_code)
            ->orderBy('id', 'DESC')
            ->first();
        return $storeReceivingInventoryModel ? $storeReceivingInventoryModel->store_name : null;
    }

    public function getFormattedLogisticsPickedUpAtReportLabelAttribute()
    {
        return $this->logistics_picked_up_at ? date('Y-m-d h:i A', strtotime($this->logistics_picked_up_at)) : null;
    }
}
