<?php

namespace App\Models\Stock;

use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOutModel extends Model
{
    use HasFactory;
    protected $table = 'stock_outs';
    protected $appends = [
        'formatted_store_name_label',
        'formatted_stock_out_date_label',
        'formatted_stock_out_date_report_label',
        'formatted_created_by_label'
    ];

    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'stock_out_date',
        'attachment',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    public function stockOutItems()
    {
        return $this->hasMany(StockOutItemModel::class, 'stock_out_id', 'id');
    }

    public static function onGenerateReferenceNumber()
    {
        $latestStockOutId = static::orderBy('id', 'desc')->value('id');
        $nextStockOutId = $latestStockOutId + 1;
        $referenceNumber = 'IO-' . str_pad($nextStockOutId, 6, '0', STR_PAD_LEFT);

        return $referenceNumber;
    }
    public function getFormattedStockOutDateLabelAttribute()
    {
        return $this->stock_out_date
            ? \Carbon\Carbon::parse($this->stock_out_date)->format('F d, Y h:i A')
            : null;
    }

    public function getFormattedCreatedByLabelAttribute()
    {
        $userModel = User::where('employee_id', $this->created_by_id)->first();
        if ($userModel) {
            return $userModel->first_name . ' ' . $userModel->last_name;
        }
        return null;
    }

    public function getFormattedStockOutDateReportLabelAttribute()
    {
        return $this->stock_out_date
            ? \Carbon\Carbon::parse($this->stock_out_date)->format('Y-m-d h:i A')
            : null;
    }

    public function getFormattedStoreNameLabelAttribute()
    {
        $storeReceivingInventoryModel = StoreReceivingInventoryItemModel::select('store_name')->where('store_code', $this->store_code)
            ->orderBy('id', 'DESC')
            ->first();
        return $storeReceivingInventoryModel ? $storeReceivingInventoryModel->store_name : null;
    }
}
