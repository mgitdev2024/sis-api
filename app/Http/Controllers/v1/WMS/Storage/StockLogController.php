<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Models\WMS\Storage\StockInventoryModel;
use App\Models\WMS\Storage\StockLogModel;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use Exception;

class StockLogController extends Controller
{
    use WmsCrudOperationsTrait;
    public function onGetByItemCode($item_code)
    {
        try {
            $whereFields = [
                'item_code' => $item_code,
            ];
            $orderFields = [
                'created_at' => 'ASC',
            ];
            return $this->readCurrentRecord(StockLogModel::class, null, $whereFields, null, $orderFields, 'Stock Logs', false, null);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception);
        }
    }
}
