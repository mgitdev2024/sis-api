<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Warehouse\WarehouseForPutAwayModel;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use Exception;

class WarehouseForPutAwayController extends Controller
{
    use WmsCrudOperationsTrait;
    public function getRules()
    {
        return [
            'created_by_id' => 'required',
            'production_items' => 'required|json',
            'warehouse_receiving_reference_number' => 'required|exists:wms_warehouse_receiving,reference_number',
            'warehouse_put_away_id' => 'required|exists:wms_warehouse_put_away,id',
            'item_code' => 'required|exists:wms_item_masterdata,item_code',
            'sub_location_id' => 'nullable|exists:wms_storage_sub_locations,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(WarehouseForPutAwayModel::class, $request, $this->getRules(), 'Warehouse For Put Away');
    }

    public function onUpdate(Request $request, $warehouse_for_put_away_id)
    {
        $fields = $request->validate([
            'warehouse_receiving_reference_number' => 'required|exists:wms_warehouse_receiving,reference_number',
            'item_code' => 'required|exists:wms_item_masterdata,item_code',
            'sub_location_id' => 'required|exists:wms_storage_sub_locations,id',
        ]);
        try {
            $warehouseForPutAwayModel = WarehouseForPutAwayModel::where('id', $warehouse_for_put_away_id)
                ->where('warehouse_receiving_reference_number', $fields['warehouse_receiving_reference_number'])
                ->where('item_code', $fields['item_code'])
                ->orderBy('id', 'DESC')
                ->first();
            if ($warehouseForPutAwayModel) {
                $permanentSubLocation = SubLocationModel::where('is_permanent', 1)
                    ->where('id', $fields['sub_location_id'])
                    ->firstOrFail();

                $warehouseForPutAwayModel->sub_location_id = $fields['sub_location_id'];
                $warehouseForPutAwayModel->save();
                return $this->dataResponse('success', 200, __('msg.update_success'));

            }
            return $this->dataResponse('success', 200, __('msg.record_not_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());

        }
    }
    public function onGetCurrent($reference_number, $created_by_id)
    {
        $warehouseForReceive = WarehouseForPutAwayModel::where('reference_number', $reference_number)
            ->where('created_by_id', $created_by_id)
            ->where('status', 1)
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
            $warehouseForReceive = WarehouseForPutAwayModel::where('reference_number', $reference_number);
            if ($warehouseForReceive->count() > 0) {
                $warehouseForReceive->delete();
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }

            return $this->dataResponse('success', 200, __('msg.record_not_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        }
    }
}
