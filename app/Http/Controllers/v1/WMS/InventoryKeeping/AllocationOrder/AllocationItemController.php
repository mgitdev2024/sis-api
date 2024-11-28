<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\AllocationOrder;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationItemModel;
use Illuminate\Http\Request;
use Exception;
use App\Traits\WMS\WmsCrudOperationsTrait;
class AllocationItemController extends Controller
{
    use WmsCrudOperationsTrait;
    public function getRules()
    {
        return [
            'allocation_order_id' => 'required|integer|exists:wms_allocation_orders,id',
            'item_id' => 'required|integer|exists:wms_item_masterdata,id',
            'request_type' => 'required',
            'theoretical_soh' => 'required|numeric|min:0',
            'store_order_quantity' => 'required|integer|min:0',
            'store_order_details' => 'required|string|max:255',
            'excess_stocks' => 'nullable|numeric|min:0',
            'allocated_stocks' => 'nullable|numeric|min:0',
            'created_by_id' => 'required'
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->getRules());
        try {
            $allocationItemModel = new AllocationItemModel();
            $allocationItemModel->fill($fields);
            $allocationItemModel->save();
            return $this->dataResponse('success', 201, 'Allocation Items ' . __('msg.create_success'), $allocationItemModel);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Allocation Items ' . __('msg.create_failed'), $exception);
        }
    }
}
