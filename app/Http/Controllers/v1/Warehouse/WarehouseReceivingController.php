<?php

namespace App\Http\Controllers\v1\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Settings\WarehouseLocationModel;
use App\Models\Warehouse\WarehouseReceivingModel;
use Illuminate\Http\Request;
use DB;
use Exception;
use App\Traits\CrudOperationsTrait;

class WarehouseReceivingController extends Controller
{
    use CrudOperationsTrait;
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
    public function onGetCurrent($referenceNumber, $status)
    {
        $whereFields = [
            'reference_number' => $referenceNumber,
            'status' => $status // 0, 1
        ];

        $orderFields = [
            'reference_number' => 'ASC'
        ];
        return $this->readCurrentRecord(WarehouseReceivingModel::class, null, $whereFields, null, $orderFields, null, 'Warehouse Receiving');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(WarehouseReceivingModel::class, $id, 'Warehouse Receiving');
    }
}
