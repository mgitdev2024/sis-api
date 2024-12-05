<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\AllocationOrder;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationItemModel;
use App\Traits\WMS\WarehouseLogTrait;
use Illuminate\Http\Request;
use Exception;
use App\Traits\WMS\WmsCrudOperationsTrait;
use DB;
class AllocationItemController extends Controller
{
    use WmsCrudOperationsTrait, WarehouseLogTrait;
    public function getRules()
    {
        return [
            'allocation_order_id' => 'required|integer|exists:wms_allocation_orders,id',
            'item_id' => 'required|integer|exists:wms_item_masterdata,id',
            'theoretical_soh' => 'required|numeric|min:0',
            'total_order_quantity' => 'required|integer|min:0',
            'store_order_details' => 'required|json',
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
            $this->createWarehouseLog(null, null, AllocationItemModel::class, $allocationItemModel->id, $allocationItemModel->getAttributes(), $fields['created_by_id'], 0);

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
            ])->first();
            if ($allocationItemModel) {
                $data = [
                    'for_allocation' => 0,
                    'area' => []
                ];
                $storeOrderDetails = json_decode($allocationItemModel->store_order_details, true);
                $data['for_allocation'] = $allocationItemModel->excess_stocks;
                foreach ($storeOrderDetails as $storeId => $storeValue) {
                    $regularOrderQuantity = $storeValue['regular_order_quantity'];
                    $advanceOrderQuantity = $storeValue['advance_order_quantity'];
                    $reservationOrderQuantity = $storeValue['reservation_order_quantity'];
                    $areaType = $storeValue['area_type'];
                    // Ensure the area type exists
                    if (!isset($data['area'][$areaType])) {
                        $data['area'][$areaType] = [
                            'area_type' => $areaType,
                        ];
                    }

                    // Ensure the store ID exists under this area type
                    if (!isset($data['area'][$areaType][$storeId])) {
                        $data['area'][$areaType][$storeId] = [
                            'short_name' => $storeValue['short_name'],
                            'long_name' => $storeValue['long_name']
                        ];
                    }

                    $data['area'][$areaType][$storeId]['regular_order_quantity'] = $regularOrderQuantity;
                    $data['area'][$areaType][$storeId]['advance_order_quantity'] = $advanceOrderQuantity;
                    $data['area'][$areaType][$storeId]['reservation_order_quantity'] = $reservationOrderQuantity;

                }

                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Allocation Items ' . __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onAllocateExcessItems(Request $request, $allocation_order_id, $itemId)
    {
        $fields = $request->validate([
            'for_allocation_adjustment' => 'required|json',
            'updated_by_id' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $allocationAdjustment = json_decode($fields['for_allocation_adjustment'], true);
            $allocationItemModel = AllocationItemModel::where([
                'allocation_order_id' => $allocation_order_id,
                'item_id' => $itemId
            ])->first();
            if ($allocationItemModel) {
                $excessStocks = $allocationItemModel->excess_stocks;
                $storeOrderDetails = json_decode($allocationItemModel->store_order_details, true);
                $updatedTotalAdjustedStocks = 0;
                foreach ($allocationAdjustment as $allocation) {
                    $storeOrderDetails[$allocation['id']]['regular_order_quantity'] += $allocation['quantity'];
                    $excessStocks -= $allocation['quantity'];
                }
                $allocationItemModel->allocated_stocks += $updatedTotalAdjustedStocks;
                $allocationItemModel->excess_stocks = $excessStocks;
                $allocationItemModel->store_order_details = json_encode($storeOrderDetails);
                $allocationItemModel->updated_by_id = $fields['updated_by_id'];
                $allocationItemModel->save();
                $this->createWarehouseLog(null, null, AllocationItemModel::class, $allocationItemModel->id, $allocationItemModel->getAttributes(), $fields['updated_by_id'], 1);

            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Allocation Items ' . __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onGet($allocation_order_id)
    {
        return $this->readCurrentRecord(AllocationItemModel::class, null, ['allocation_order_id' => $allocation_order_id], null, null, 'Allocation Item');
    }
}
