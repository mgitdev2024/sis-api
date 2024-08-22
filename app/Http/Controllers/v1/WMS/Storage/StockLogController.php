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
    public function onGetByItemCode($item_code, $date = null)
    {
        try {
            $stockLogModel = StockLogModel::where([
                'item_code' => $item_code
            ]);
            if ($date) {
                $stockLogModel->whereDate('created_at', $date);
            }
            $stockLogModel->selectRaw("*, DATE_FORMAT(created_at, '%Y-%m-%d') as formatted_date")
                ->selectRaw("CASE WHEN action = 1 THEN 'Inbound' ELSE 'Outbound' END as action_label");
            $stockLogModel = $stockLogModel->get();
            return $this->dataResponse('success', 200, 'Stock Log ' . __('msg.record_found'), $stockLogModel);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception);
        }
    }
}
