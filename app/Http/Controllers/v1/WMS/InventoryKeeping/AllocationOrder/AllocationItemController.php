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
            'total_order_quantity' => 'required|integer|min:0',
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
            return $this->dataResponse('error', 400, 'Allocation Items ' . __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onGetStoreOrderDetails($allocation_order_id, $item_id)
    {
        try {
            $allocationItemModel = AllocationItemModel::where([
                'allocation_order_id' => $allocation_order_id,
                'item_id' => $item_id
            ])->get();
            if (count($allocationItemModel) > 0) {
                $data = [
                    'for_allocation' => 0,
                    'area' => []
                ];

                foreach ($allocationItemModel as $storeOrder) {
                    $storeOrderDetails = json_decode($storeOrder->store_order_details, true);
                    $data['for_allocation'] += $storeOrder['excess_stocks'];
                    foreach ($storeOrderDetails as $storeId => $storeValue) {
                        $requestType = $storeOrder['request_type'];
                        $areaId = $storeValue['area_id'];
                        $areaType = $storeValue['area_type'];
                        $orderQuantity = $storeValue['order_quantity'];
                        // Ensure the area type exists
                        if (!isset($data['area'][$areaId])) {
                            $data['area'][$areaId] = [
                                'area_type' => $areaType,
                                'area_id' => $areaId
                            ];
                        }

                        // Ensure the store ID exists under this area type
                        if (!isset($data['area'][$areaId][$storeId])) {
                            $data['area'][$areaId][$storeId] = [
                                'short_name' => $storeValue['short_name'],
                                'long_name' => $storeValue['long_name']
                            ];
                        }

                        // Add or update the request type quantity for this store ID
                        if (!isset($data['area'][$areaId][$storeId][$requestType])) {
                            $data['area'][$areaId][$storeId][$requestType] = 0;
                        }

                        // Sum the order quantity
                        $data['area'][$areaId][$storeId][$requestType] += $orderQuantity;
                    }
                }

                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Allocation Items ' . __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
