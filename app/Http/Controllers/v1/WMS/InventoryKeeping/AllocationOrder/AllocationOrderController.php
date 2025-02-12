<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\AllocationOrder;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationItemModel;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationOrderModel;
use App\Traits\WMS\WarehouseLogTrait;
use Illuminate\Http\Request;
use Exception;
use App\Traits\WMS\WmsCrudOperationsTrait;
use DB;
class AllocationOrderController extends Controller
{
    use WmsCrudOperationsTrait, WarehouseLogTrait;

    public function getRules()
    {
        return [
            'consolidation_reference_number' => 'required',
            'created_by_id' => 'required',
            'consolidated_by' => 'required',
            'delivery_type_code' => 'required',
            'estimated_delivery_date' => 'required',
            'consolidated_items' => 'required|json',
        ];
    }
    public function onCreate(Request $request)
    {
        try {
            $fields = $request->validate($this->getRules());
            $existingAllocationOrderModel = AllocationOrderModel::where('delivery_type_code', 'like', $fields['delivery_type_code'] . '%')
                ->where('consolidation_reference_number', $fields['consolidation_reference_number'])
                ->first();
            $consolidatedItems = json_decode($fields['consolidated_items'], true);

            DB::beginTransaction();
            if ($existingAllocationOrderModel) {
                $this->onInitializeAllocationItems($consolidatedItems, $existingAllocationOrderModel->id, $fields['created_by_id']);

            } else {
                $allocationOrderModel = new AllocationOrderModel();
                $allocationOrderModel->consolidation_reference_number = $fields['consolidation_reference_number'];
                $allocationOrderModel->consolidated_by = $fields['consolidated_by'];
                $allocationOrderModel->delivery_type_code = $fields['delivery_type_code'];
                $allocationOrderModel->estimated_delivery_date = $fields['estimated_delivery_date'];
                $allocationOrderModel->created_by_id = $fields['created_by_id'];
                $allocationOrderModel->save();
                $this->createWarehouseLog(null, null, AllocationItemModel::class, $allocationOrderModel->id, $allocationOrderModel->getAttributes(), $fields['created_by_id'], 0);

                $this->onInitializeAllocationItems($consolidatedItems, $allocationOrderModel->id, $fields['created_by_id']);
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Allocation Orders ' . __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onInitializeAllocationItems($consolidatedItems, $allocationOrderModelId, $createdById)
    {
        foreach ($consolidatedItems as $key => $value) {
            $existingAllocationItems = AllocationItemModel::where([
                'allocation_order_id' => $allocationOrderModelId,
                'item_id' => $key,
            ])->first();
            if ($existingAllocationItems) {
                $this->onUpdateAllocationItems($value, $existingAllocationItems, $createdById);
            } else {
                $this->onAddAllocationItems($key, $value, $allocationOrderModelId, $createdById);
            }
        }
    }

    public function onAddAllocationItems($key, $value, $allocationOrderModelId, $createdById)
    {
        $allocationItemController = new AllocationItemController();
        $allocationItemRequest = new Request([
            'allocation_order_id' => $allocationOrderModelId,
            'item_id' => $key,
            'theoretical_soh' => $value['theoretical_soh'],
            'total_order_quantity' => $value['total_order_quantity'],
            'store_order_details' => json_encode($value['store_order_details']),
            'excess_stocks' => $value['excess_stocks'],
            'allocated_stocks' => $value['allocated_stocks'],
            'created_by_id' => $createdById,
        ]);
        $allocationItemController->onCreate($allocationItemRequest);
    }

    public function onUpdateAllocationItems($value, $existingAllocationItems, $createdById)
    {
        $existingAllocationItems->theoretical_soh += $value['theoretical_soh'];
        $existingAllocationItems->total_order_quantity += $value['total_order_quantity'];
        $existingAllocationItems->excess_stocks += $value['excess_stocks'];
        $existingAllocationItems->allocated_stocks += $value['allocated_stocks'];
        $existingStoreOrderDetails = json_decode($existingAllocationItems->store_order_details, true);
        $mergedArray = $existingStoreOrderDetails + $value['store_order_details'];
        $existingAllocationItems->store_order_details = json_encode($mergedArray);
        $existingAllocationItems->updated_by_id = $createdById;
        $existingAllocationItems->save();
        $this->createWarehouseLog(null, null, AllocationItemModel::class, $existingAllocationItems->id, $existingAllocationItems->getAttributes(), $createdById, 1);
    }

    public function onGet($status, $filter = null)
    {
        try {
            // put date filtering
            $allocationOrderModel = AllocationOrderModel::whereIn('status', [0, 1, 2]);
            $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);
            if ($whereObject) {
                $allocationOrderModel->whereDate('created_at', $filter);
            } else if ($status != 0) {
                $yesterday = (new \DateTime('yesterday'))->format('Y-m-d 00:00:00');
                $today = (new \DateTime('today'))->format('Y-m-d 23:59:59');
                $allocationOrderModel->whereBetween('created_at', [$yesterday, $today]);
            }
            $allocationOrderModel->orderBy('created_at', 'DESC');

            $allocationOrderModel = $allocationOrderModel->get();
            return $this->dataResponse('success', 200, 'Allocation Order', $allocationOrderModel);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Allocation Order ' . __('msg.record_not_found'));
        }
    }
}
