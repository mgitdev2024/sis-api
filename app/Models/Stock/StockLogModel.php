<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Illuminate\Support\Carbon;
class StockLogModel extends Model
{
    use HasFactory;

    protected $table = 'stock_logs';

    protected $appends = [
        'formatted_created_at',
    ];
    protected $fillable = [
        'reference_number',
        'store_code',
        'store_sub_unit_short_name',
        'item_code',
        'item_description',
        'item_category_name',
        'quantity',
        'initial_stock',
        'final_stock',
        'transaction_items',
        'transaction_type',
        'transaction_sub_type',
        'status',
        'created_by_id',
        'updated_by_id',
    ];


    public static function onGetBeginningStock($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName)
    {
        try {
            $transactionDate = date('Y-m-d', strtotime("-1 day " . $transactionDate));
            $stockLogModel = self::where([
                'item_code' => $itemCode,
                'store_code' => $storeCode,
            ]);
            if ($storeSubUnitShortName) {
                $stockLogModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $stockLogModel = $stockLogModel->whereDate('created_at', $transactionDate)
                ->orderBy('id', 'DESC')->first();
            if ($stockLogModel) {
                return $stockLogModel->final_stock;
            }
            return 0;
        } catch (Exception $exception) {
            return 0;
        }
    }

    public static function onGetActualStock($transactionDate, $itemCode, $storeCode, $storeSubUnitShortName)
    {
        try {
            $stockLogModel = self::where([
                'item_code' => $itemCode,
                'store_code' => $storeCode,
            ]);
            if ($storeSubUnitShortName) {
                $stockLogModel->where('store_sub_unit_short_name', $storeSubUnitShortName);
            }
            $stockLogModel = $stockLogModel->whereDate('created_at', $transactionDate)
                ->orderBy('id', 'DESC')->first();
            if ($stockLogModel) {
                return $stockLogModel->final_stock;
            }
            return 0;
        } catch (Exception $exception) {
            return 0;
        }
    }
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at
            ? Carbon::parse($this->created_at)->format('Y-m-d h:i A')
            : null;
    }
}
