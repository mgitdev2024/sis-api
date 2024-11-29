<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\AllocationOrder;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationItemModel;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationOrderModel;
use Illuminate\Http\Request;
use Exception;
use App\Traits\WMS\WmsCrudOperationsTrait;
use DB;
class AllocationOrderController extends Controller
{
    use WmsCrudOperationsTrait;

    public function getRules()
    {
        return [
            'reference_number' => 'required',
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
                ->where('reference_number', $fields['reference_number'])
                ->first();
            $consolidatedItems = json_decode($fields['consolidated_items'], true);

            DB::beginTransaction();
            if ($existingAllocationOrderModel) {
                $this->onInitializeAllocationItems($consolidatedItems, $existingAllocationOrderModel->id, $fields['created_by_id']);

            } else {
                $allocationOrderModel = new AllocationOrderModel();
                $allocationOrderModel->reference_number = $fields['reference_number'];
                $allocationOrderModel->consolidated_by = $fields['consolidated_by'];
                $allocationOrderModel->delivery_type_code = $fields['delivery_type_code'];
                $allocationOrderModel->estimated_delivery_date = $fields['estimated_delivery_date'];
                $allocationOrderModel->created_by_id = $fields['created_by_id'];
                $allocationOrderModel->save();

                $this->onInitializeAllocationItems($consolidatedItems, $allocationOrderModel->id, $fields['created_by_id']);
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Allocation Orders ' . __('msg.create_failed'), $exception);
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
            'request_type' => $value['request_type'],
            'theoretical_soh' => $value['theoretical_soh'],
            'store_order_quantity' => $value['store_order_quantity'],
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
        $existingAllocationItems->store_order_quantity += $value['store_order_quantity'];
        $existingAllocationItems->excess_stocks += $value['excess_stocks'];
        $existingAllocationItems->allocated_stocks += $value['allocated_stocks'];
        $existingAllocationItems->store_order_details = json_encode(array_merge(json_decode($existingAllocationItems->store_order_details, true), $value['store_order_details']));
        $existingAllocationItems->updated_by_id = $createdById;
        $existingAllocationItems->save();
    }
}
