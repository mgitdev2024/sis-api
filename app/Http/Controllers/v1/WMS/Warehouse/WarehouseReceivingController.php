<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Settings\WarehouseLocationModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use Illuminate\Http\Request;
use DB;
use Exception;
use App\Traits\MOS\MosCrudOperationsTrait;

class WarehouseReceivingController extends Controller
{
    use MosCrudOperationsTrait;
    public function onGetAllCategory($status)
    {
        try {
            $itemDisposition = WarehouseReceivingModel::select(
                'reference_number',
                DB::raw('count(*) as batch_count'),
                DB::raw('SUM(JSON_LENGTH(produced_items))  as produced_items_count')
            )
                ->where('status', $status)
                ->groupBy([
                    'reference_number',
                ])
                ->get();
            $warehouseReceiving = [];
            $counter = 0;
            foreach ($itemDisposition as $value) {
                $warehouseReceiving[$counter] = [
                    'reference_number' => $value->reference_number,
                    'batch_count' => $value->batch_count,
                    'quantity' => $value->produced_items_count,
                ];
                ++$counter;
            }
            if (count($warehouseReceiving) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $warehouseReceiving);
            }
            return $this->dataResponse('error', 200, WarehouseReceivingModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    #region Separate batches per item code
    // public function onGetCurrent($referenceNumber, $status)
    // {
    //     $whereFields = [
    //         'reference_number' => $referenceNumber,
    //         'status' => $status // 0, 1
    //     ];

    //     $orderFields = [
    //         'reference_number' => 'ASC'
    //     ];
    //     return $this->readCurrentRecord(WarehouseReceivingModel::class, null, $whereFields, null, $orderFields, null, 'Warehouse Receiving');
    // }
    #endregion
    public function onGetCurrent($referenceNumber, $status)
    {
        try {
            $itemDisposition = WarehouseReceivingModel::select(
                'reference_number',
                'item_code',
                DB::raw('SUM(JSON_LENGTH(produced_items)) as produced_items_count')
            )
                ->where('status', $status)
                ->where('reference_number', $referenceNumber)
                ->groupBy([
                    'item_code',
                    'reference_number'
                ])
                ->get();
            $warehouseReceiving = [];
            $counter = 0;
            foreach ($itemDisposition as $value) {
                $warehouseReceiving[$counter] = [
                    'reference_number' => $value->reference_number,
                    'quantity' => $value->produced_items_count,
                    'item_code' => $value->item_code,
                    'sku_type' => ItemMasterdataModel::where('item_code', $value->item_code)->first()->item_category_label
                ];
                ++$counter;
            }
            if (count($warehouseReceiving) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $warehouseReceiving);
            }
            return $this->dataResponse('error', 200, WarehouseReceivingModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetById($id)
    {
        return $this->readRecordById(WarehouseReceivingModel::class, $id, 'Warehouse Receiving');
    }
    public function onUpdate(Request $request)
    {
        try {

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, WarehouseReceivingModel::class . ' ' . __('msg.update_failed'));
        }
    }
}
