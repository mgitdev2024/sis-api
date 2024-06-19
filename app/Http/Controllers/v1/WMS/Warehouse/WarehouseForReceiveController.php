<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WMS\Warehouse\WarehouseForReceiveModel;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;

class WarehouseForReceiveController extends Controller
{
    use WmsCrudOperationsTrait;

    public function getRules()
    {
        return [
            'created_by_id' => 'required',
            'production_items' => 'required',
            'reference_number' => 'required|exists:wms_warehouse_receiving',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(WarehouseForReceiveModel::class, $request, $this->getRules(), 'Warehouse For Receive');
    }

    public function onGetCurrent($reference_number, $created_by_id)
    {
        $whereFields = [
            'reference_number' => $reference_number,
            'created_by_id' => $created_by_id
        ];

        $warehouseForReceive = WarehouseForReceiveModel::where('reference_number', $reference_number)
            ->where('created_by_id', $created_by_id)
            ->orderBy('id', 'DESC')
            ->first();

        if ($warehouseForReceive) {
            return $this->dataResponse('success', 200, __('msg.record_found'), $warehouseForReceive);
        }
        return $this->dataResponse('success', 200, __('msg.record_not_found'), $warehouseForReceive);
    }
    public function onDelete($reference_number)
    {
        try {
            $warehouseForReceive = WarehouseForReceiveModel::where('reference_number', $reference_number);
            if ($warehouseForReceive->count() > 0) {
                $warehouseForReceive->delete();
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }

            return $this->dataResponse('success', 200, __('msg.record_not_found'));

        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        }
    }
}
