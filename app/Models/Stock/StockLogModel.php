<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
class StockLogModel extends Model
{
    use HasFactory;

    protected $table = 'stock_logs';

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


    public static function onGetBeginningStock($transactionDate, $itemCode)
    {
        try {
            $transactionDate = date('Y-m-d', strtotime("-1 day " . $transactionDate));
            $stockLogModel = self::where('item_code', $itemCode)->whereDate('created_at', $transactionDate)->orderBy('id', 'DESC')->first();
            if ($stockLogModel) {
                return $stockLogModel->final_stock;
            }
            return 0;
        } catch (Exception $exception) {
            return 0;
        }
    }
}
