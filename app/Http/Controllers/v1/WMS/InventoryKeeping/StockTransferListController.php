<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\StockTransferListModel;
use Illuminate\Http\Request;
use DB;
use Exception;
class StockTransferListController extends Controller
{
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'reason' => 'required|string',
            'items_to_transfer' => 'required|json',
            'zone_id' => 'required|integer|exists:wms_storage_zones,id',
        ]);
        try {
            DB::beginTransaction();
            $stockTransferListModel = new StockTransferListModel();
            dd(json_decode($fields['items_to_transfer'], true));
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
