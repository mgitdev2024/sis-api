<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\GeneratePicklist;

use App\Http\Controllers\Controller;
use App\Models\WMS\InventoryKeeping\GeneratePicklist\GeneratePickListItemModel;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationItemModel;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationOrderModel;
use App\Models\WMS\InventoryKeeping\GeneratePickList\GeneratePickListModel;
use Illuminate\Http\Request;
use App\Traits\WMS\WarehouseLogTrait;
use Exception;
use App\Traits\WMS\WmsCrudOperationsTrait;
use DB;
class GeneratePickListController extends Controller
{
    use WmsCrudOperationsTrait, WarehouseLogTrait;

    /**region PICKING TYPE
    # 0 = DISCREET
    # 1 = BATCH
    */
    public function getRules()
    {
        return [
            'allocation_order_id' => 'required|exists:wms_allocation_orders,id',
            'created_by_id' => 'required',
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->getRules());
        try {
            DB::beginTransaction();
            $allocationOrderModel = AllocationOrderModel::find($fields['allocation_order_id']);

            if (!GeneratePickListModel::where('allocation_order_id', $fields['allocation_order_id'])->exists()) {
                // Generate Picklist Add
                $picklistModel = new GeneratePickListModel();
                $picklistModel->reference_number = GeneratePickListModel::onGeneratePickListReferenceNumber();
                $picklistModel->allocation_order_id = $fields['allocation_order_id'];
                $picklistModel->consolidation_reference_number = $allocationOrderModel->consolidation_reference_number;
                $picklistModel->created_by_id = $fields['created_by_id'];
                $picklistModel->save();
                $this->createWarehouseLog(null, null, GeneratePickListModel::class, $picklistModel->id, $picklistModel->getAttributes(), $fields['created_by_id'], 0);

                // Allocation Order Update
                $allocationOrderModel->status = 2;
                $allocationOrderModel->save();
                $this->createWarehouseLog(null, null, AllocationOrderModel::class, $allocationOrderModel->id, $allocationOrderModel->getAttributes(), $fields['created_by_id'], 1);
                DB::commit();
                return $this->dataResponse('success', 201, 'Generate Picklist ' . __('msg.create_success'), $picklistModel);
            }
            return $this->dataResponse('success', 200, 'Generate Picklist Already Created');

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Generate Picklist ' . __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onGet($status = 0, $filter = null)
    {
        try {
            $picklistModel = GeneratePickListModel::with('allocationOrder')
                ->where('status', $status);
            $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);
            if ($whereObject) {
                $picklistModel->whereDate('created_at', $filter);
            } else if ($status != 0) {
                $yesterday = (new \DateTime('yesterday'))->format('Y-m-d 00:00:00');
                $today = (new \DateTime('today'))->format('Y-m-d 23:59:59');
                $picklistModel->whereBetween('created_at', [$yesterday, $today]);
            }
            $picklistModel->orderBy('created_at', 'DESC');

            $picklistModel = $picklistModel->get();
            return $this->dataResponse('success', 200, 'Generate Picklist ' . __('msg.record_found'), $picklistModel);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Generate Picklist ' . __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetByPickingType($type, $status, $generate_picklist_id = null)
    {
        try {
            $generatePicklistModel = GeneratePickListModel::where('status', $status);
            if ($generate_picklist_id != null) {
                $generatePicklistModel->where('id', $generate_picklist_id);
            }
            $generatePicklistModel = $generatePicklistModel->get();

            $allocationItems = AllocationItemModel::whereIn('allocation_order_id', $generatePicklistModel->pluck('allocation_order_id'))
                ->get()
                ->groupBy('allocation_order_id');
            $data = $this->onGetStoreAreasAndOrders($generatePicklistModel, $allocationItems, $type);

            return $this->dataResponse('success', 200, 'Generate Picklist ' . __('msg.record_found'), $data);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Generate Picklist ' . __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetStoreAreasAndOrders($generatePicklistModel, $allocationItems, $type)
    {
        $data = [];
        $storeToRoute = [];
        // SAMPLE DATA FOR ROUTE, SHOULD BE CALLED THRU API
        $routes = [
            'North E1' => [2, 3, 4, 6, 7, 8, 9, 10, 14, 16, 26, 28, 33, 37, 39, 40, 42, 50, 54, 60, 69, 71, 77, 80, 85, 93, 98, 100, 105, 107, 115, 117, 130, 131, 142],
            'South D1' => [1, 12, 29, 51, 59, 65, 68, 74, 83, 87, 88, 91, 92, 94, 96, 108, 111, 113, 118, 127, 128, 138, 140, 144, 149, 152],
            'East W2' => [5, 13, 20, 23, 34, 44, 45, 48, 49, 52, 57, 58, 64, 72, 84, 99, 106, 123, 126, 137, 143, 147],
            'West A3' => [11, 18, 22, 35, 46, 56, 61, 66, 67, 70, 75, 76, 78, 81, 82, 89, 90, 97, 103, 109, 110, 112, 114, 120, 124, 129, 133, 134, 139, 141, 148, 150, 151],
            'Central Z4' => [15, 17, 19, 21, 24, 25, 27, 30, 31, 32, 36, 38, 41, 43, 47, 53, 55, 62, 63, 73, 79, 86, 95, 101, 102, 104, 116, 119, 121, 122, 125, 132, 135, 136, 145, 146, 201, 202],
        ];
        foreach ($routes as $routeName => $stores) {
            foreach ($stores as $storeId) {
                $storeToRoute[$storeId] = $routeName;
            }
        }

        foreach ($generatePicklistModel as $picklist) {
            if (isset($allocationItems[$picklist->allocation_order_id])) {
                // Item ID Allocation Loop
                foreach ($allocationItems[$picklist->allocation_order_id] as $allocationItemValue) {
                    $storeOrderDetails = json_decode($allocationItemValue->store_order_details, true);
                    $itemMasterdata = $allocationItemValue->itemMasterdata;
                    $itemId = $itemMasterdata->id;
                    if (!$itemMasterdata->picking_type == $type) {
                        continue;
                    }

                    // Store Loop
                    foreach ($storeOrderDetails as $storeId => $storeValue) {
                        $storeRoute = $type == 0 ? $storeToRoute[$storeId] . '-' . $picklist->reference_number : $picklist->reference_number;
                        if (!isset($data[$storeRoute])) {
                            $data[$storeRoute] = [
                                'total_item_count' => 0,
                            ];
                            $data[$storeRoute]['reference_number'] = $picklist->reference_number;
                            $data[$storeRoute]['generate_picklist_id'] = $picklist->id;

                            if ($type == 0) {
                                $data[$storeRoute]['stores'] = [];
                                $data[$storeRoute]['route_name'] = $storeToRoute[$storeId];
                            } else {
                                $data[$storeRoute]['items'] = [];
                            }
                        }

                        if ($type == 0) {
                            if (!isset($data[$storeRoute]['stores'][$storeId])) {
                                $data[$storeRoute]['stores'][$storeId] = [
                                    'items' => [],
                                    'short_name' => $storeValue['short_name'],
                                    'area_type' => $storeValue['area_type'],
                                    'id' => $storeId,
                                ];
                            }

                            if (!isset($data[$storeRoute]['stores'][$storeId]['items'][$itemId])) {
                                $data[$storeRoute]['stores'][$storeId]['items'][$itemId] = [
                                    'item_code' => $itemMasterdata->item_code,
                                    'item_description' => $itemMasterdata->description,
                                    'item_category_label' => $itemMasterdata->itemCategory->name,
                                    'item_attachment' => $itemMasterdata->attachment,
                                    'item_id' => $itemId,
                                    'regular_order_quantity' => $storeValue['regular_order_quantity']
                                ];
                                $alreadyPickedData = $this->onCheckPickedData($storeId, $itemId, $picklist, 0);
                                $data[$storeRoute]['stores'][$storeId]['items'][$itemId]['picked_scanned_quantity'] = $alreadyPickedData['picked_scanned_quantity'];
                                $data[$storeRoute]['stores'][$storeId]['items'][$itemId]['checked_quantity'] = $alreadyPickedData['checked_quantity'];
                                $data[$storeRoute]['stores'][$storeId]['items'][$itemId]['for_dispatch_quantity'] = $alreadyPickedData['for_dispatch_quantity'];
                                $data[$storeRoute]['stores'][$storeId]['is_picked'] = $alreadyPickedData['is_picked'];
                            } else {
                                // If item already exists, update the regular_order_quantity
                                $data[$storeRoute]['stores'][$storeId]['items'][$itemId]['regular_order_quantity'] += $storeValue['regular_order_quantity'];
                            }
                            $data[$storeRoute]['total_item_count']++;

                        } else if ($type == 1) {
                            if (!isset($data[$storeRoute]['items'][$itemId])) {
                                $data[$storeRoute]['items'][$itemId] = [
                                    'item_code' => $itemMasterdata->item_code,
                                    'item_description' => $itemMasterdata->description,
                                    'item_attachment' => $itemMasterdata->attachment,
                                    'item_id' => $itemId,
                                    'regular_order_quantity' => $storeValue['regular_order_quantity']
                                ];
                                $alreadyPickedData = $this->onCheckPickedData($storeId, $itemId, $picklist, 1);
                                $data[$storeRoute]['items'][$itemId]['picked_scanned_quantity'] = $alreadyPickedData['picked_scanned_quantity'];
                                $data[$storeRoute]['items'][$itemId]['checked_quantity'] = $alreadyPickedData['checked_quantity'];
                                $data[$storeRoute]['items'][$itemId]['for_dispatch_quantity'] = $alreadyPickedData['for_dispatch_quantity'];
                                $data[$storeRoute]['items'][$itemId]['is_picked'] = $alreadyPickedData['is_picked'];
                            } else {
                                // If item already exists, update the regular_order_quantity
                                $data[$storeRoute]['items'][$itemId]['regular_order_quantity'] += $storeValue['regular_order_quantity'];
                            }
                            $data[$storeRoute]['total_item_count'] += $storeValue['regular_order_quantity'];
                        }
                    }
                }
            }
        }
        return $data;
    }

    public function onCheckPickedData($storeId, $itemId, $picklist, $picklistType)
    {
        $data = [
            'picked_scanned_quantity' => 0,
            'checked_quantity' => 0,
            'for_dispatch_quantity' => 0,
            'is_picked' => false,
        ];
        $generatePicklistItems = GeneratePickListItemModel::where([
            'generate_picklist_id' => $picklist->id,
        ]);

        if ($picklistType == 0) {
            $generatePicklistItems->where('store_id', $storeId);
        }
        $generatePicklistItems = $generatePicklistItems->first();

        if ($generatePicklistItems) {
            $picklistItems = json_decode($generatePicklistItems->picklist_items, true);
            if (isset($picklistItems[$itemId])) {
                $data['picked_scanned_quantity'] = $picklistItems[$itemId]['picked_scanned_quantity'] ?? 0;
                $data['checked_quantity'] = $picklistItems[$itemId]['checked_quantity'] ?? 0;
                $data['for_dispatch_quantity'] = $picklistItems[$itemId]['for_dispatch_quantity'] ?? 0;
                $data['is_picked'] = $picklistItems[$itemId]['picked_scanned_quantity'] > 0;
            }
        }
        return $data;
    }
}
