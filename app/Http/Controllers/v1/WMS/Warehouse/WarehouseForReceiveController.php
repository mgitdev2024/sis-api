<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Warehouse\WarehouseForReceiveModel;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

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
        $warehouseForReceive = WarehouseForReceiveModel::where('reference_number', $reference_number)
            ->where('created_by_id', $created_by_id)
            // ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->first();

        if ($warehouseForReceive) {
            $data = $warehouseForReceive;
            $productionItems = json_decode($data->production_items, true);
            foreach ($productionItems as &$items) {
                $items['item_code'] = ItemMasterdataModel::where('id', $items['item_id'])->first()->item_code;
                unset($items);
            }
            $data->production_items = json_encode($productionItems);

            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
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

        } catch (QueryException $exception) {
            if ($exception->getCode() == 23000) {
                return $this->dataResponse('error', 400, __('msg.delete_failed_fk_constraint', ['modelName' => 'Warehouse For Receive']));
            }
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        }
    }
}
