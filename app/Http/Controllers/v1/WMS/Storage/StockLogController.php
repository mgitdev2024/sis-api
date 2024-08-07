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

    public function testingStock(Request $request)
    {
        $fields = $request->validate([
            'item_code' => 'required|exists:wms_item_masterdata,item_code',
            'quantity' => 'required|integer'
        ]);
        try {
            $stockInventory = StockInventoryModel::where('item_code', $fields['item_code'])->first();
            $currentStock = 0;
            if ($stockInventory) {
                $currentStock = $stockInventory->stock_count;
            }
            $totalCurrentStock = $currentStock + $fields['quantity'];
            $stockLog = new StockLogModel();
            $stockLog->item_code = $fields['item_code'];
            $stockLog->reference_number = '8000001';
            $stockLog->action = 1;
            $stockLog->quantity = $fields['quantity'];
            $stockLog->sub_location_id = 51;
            $stockLog->layer_level = 2;
            $stockLog->storage_remaining_space = 12;
            $stockLog->created_by_id = 1;
            $stockLog->initial_stock = $currentStock;
            $stockLog->final_stock = $totalCurrentStock;
            $stockLog->save();

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception);
        }
    }
}
