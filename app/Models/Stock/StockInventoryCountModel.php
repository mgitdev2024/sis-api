<?php

namespace App\Models\Stock;

use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInventoryCountModel extends Model
{
    use HasFactory;

    protected $table = 'stock_inventory_count';

    protected $appends = [
        'type_label',
        'status_label',
        'formatted_store_name_label',
        'formatted_created_by_label',
        'formatted_updated_by_label',
        'formatted_created_at_label',
        'formatted_updated_at_label',
        'formatted_reviewed_at_label',
        'formatted_posted_at_label',
        'formatted_reviewed_by_label',
        'formatted_posted_by_label',
    ];
    protected $fillable = [
        'reference_number',
        'type', // 1 = Hourly, 2 = EOD, 3 = Month-End
        'store_code',
        'store_sub_unit_short_name',
        'created_at',
        'created_by_id',
        'updated_at',
        'updated_by_id',
        'reviewed_at',
        'reviewed_by_id',
        'posted_at',
        'posted_by_id',
        'status', // 0 = Pending, 1 = For Review, 2 = Posted, 3 = Cancelled
    ];

    public function stockInventoryItemsCount()
    {
        return $this->hasMany(StockInventoryItemCountModel::class, 'stock_inventory_count_id', 'id');
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

    public function getStatusLabelAttribute()
    {
        $status = [
            0 => 'Pending',
            1 => 'For Review',
            2 => 'Posted',
            3 => 'Cancelled',
        ];
        return $status[$this->status] ?? 'Unknown';
    }

    public function getFormattedStoreNameLabelAttribute()
    {
        $storeReceivingInventoryModel = StoreReceivingInventoryItemModel::select('store_name')->where('store_code', $this->store_code)
            ->orderBy('id', 'DESC')
            ->first();
        return $storeReceivingInventoryModel ? $storeReceivingInventoryModel->store_name : null;
    }
    public function getFormattedCreatedByLabelAttribute()
    {
        $userModel = User::where('employee_id', $this->created_by_id)->first();
        if ($userModel) {
            return $userModel->first_name . ' ' . $userModel->last_name;
        }
        return null;
    }
    public function getFormattedUpdatedByLabelAttribute()
    {
        $userModel = User::where('employee_id', $this->updated_by_id)->first();
        if ($userModel) {
            return $userModel->first_name . ' ' . $userModel->last_name;
        }
        return null;
    }

    public function getFormattedCreatedAtLabelAttribute()
    {
        return $this->created_at ? \Carbon\Carbon::parse($this->created_at)->format('Y-m-d h:i A') : null;
    }

    public function getFormattedUpdatedAtLabelAttribute()
    {
        return $this->updated_at ? \Carbon\Carbon::parse($this->updated_at)->format('Y-m-d h:i A') : null;
    }

    public function getFormattedReviewedAtLabelAttribute()
    {
        return $this->reviewed_at ? \Carbon\Carbon::parse($this->reviewed_at)->format('Y-m-d h:i A') : null;
    }

    public function getFormattedPostedAtLabelAttribute()
    {
        return $this->posted_at ? \Carbon\Carbon::parse($this->posted_at)->format('Y-m-d h:i A') : null;
    }

    public function getFormattedReviewedByLabelAttribute()
    {
        $userModel = User::where('employee_id', $this->reviewed_by_id)->first();
        if ($userModel) {
            return $userModel->first_name . ' ' . $userModel->last_name;
        }
        return null;
    }

    public function getFormattedPostedByLabelAttribute()
    {
        $userModel = User::where('employee_id', $this->posted_by_id)->first();
        if ($userModel) {
            return $userModel->first_name . ' ' . $userModel->last_name;
        }
        return null;
    }
}
