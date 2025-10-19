<?php

namespace App\Http\Controllers\v1\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreReceivingGoodsIssueItemModel;
use App\Models\Store\StoreReceivingGoodsIssueModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Models\Store\StoreReceivingInventoryModel;
use App\Traits\Sap\SapGoodsIssueTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Support\Str;
class StoreReceivingInventoryController extends Controller
{
    // --------------------- SAP Functions --------------------------------
    use ResponseTrait, SapGoodsIssueTrait;
    public function onCreateReceivingFromGI()
    {
        try {
            DB::beginTransaction();
            $data = $this->getOutboundGoodsIssue();
            $collatedData = $this->collateGoodsIssueData($data);
            $counter = 1;
            foreach ($collatedData as $goodsIssue) {
                $createdByName = 'SAP System Generated';
                $createdById = '0000';

                $warehouseCode = $goodsIssue['warehouse_code'];
                $warehouseName = $goodsIssue['warehouse_name'];
                $deliveryDate = date('Y-m-d', strtotime($goodsIssue['delivery_date']));
                $deliveryType = $goodsIssue['delivery_type'];
                $giPostingDate = date('Y-m-d', strtotime($goodsIssue['gi_posting_date']));
                $giPlant = $goodsIssue['gi_plant'];

                $insertData = [];
                $sapGoodsIssueData = [];
                $latestConsolidatedOrder = StoreReceivingInventoryModel::latest()->first();
                $latestConsolidatedId = $latestConsolidatedOrder ? $latestConsolidatedOrder->consolidated_order_id + 1 : 1;
                $generatedReferenceNumber = StoreReceivingInventoryModel::onGenerateReferenceNumber($latestConsolidatedId);
                $storeReceivingInventory = StoreReceivingInventoryModel::create([
                    'consolidated_order_id' => $latestConsolidatedId,
                    'warehouse_code' => $warehouseCode,
                    'warehouse_name' => $warehouseName,
                    'reference_number' => $generatedReferenceNumber,
                    'delivery_date' => $deliveryDate,
                    'delivery_type' => $deliveryType,
                    'is_sap_created' => 1,
                    'created_by_name' => $createdByName,
                    'created_by_id' => $createdById,
                    'updated_by_id' => $createdById,
                ]);

                StoreReceivingGoodsIssueModel::insert([
                    'sr_inventory_id' => $storeReceivingInventory->id,
                    'gi_posting_date' => $giPostingDate,
                    'gi_plant_code' => $giPlant,
                    'gi_plant_name' => $warehouseName,
                    'created_by_id' => $createdById,
                    'updated_by_id' => $createdById,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($goodsIssue['sessions'] as $storeOrders) {
                    $storeCode = $storeOrders['store_code'];
                    $storeName = $storeOrders['store_name'];
                    $deliveryType = $storeOrders['delivery_type'];
                    $orderDate = $storeOrders['order_date'];
                    $orderSessionId = $storeOrders['order_session_id'] ?? null;
                    // $storeSubUnitId = $storeOrders['store_sub_unit_id'];
                    $storeSubUnitShortName = $storeOrders['store_sub_unit_short_name'] == 1 ? 'FOH' : 'BOH';
                    $storeSubUnitLongName = $storeOrders['store_sub_unit_long_name'] == 1 ? 'Front of the House' : 'Back of the House';
                    $orderReferenceNumber = 'GI-' . $storeOrders['order_session_id'];

                    $exists = StoreReceivingInventoryItemModel::where('reference_number', $orderReferenceNumber)->exists();
                    if ($exists) {
                        throw new Exception('Reference number already exists: ' . $orderReferenceNumber);
                    }
                    if (isset($storeOrders['ordered_items'])) {
                        foreach ($storeOrders['ordered_items'] as $orderedItems) {

                            if ($storeOrders['gi_material_doc'] !== '4900000163') {
                                continue;
                            }
                            $generateUniqueId = Str::uuid()->toString();
                            $insertData[] = [
                                'store_receiving_inventory_id' => $storeReceivingInventory->id,
                                'order_type' => $orderedItems['order_type'] ?? 0,
                                'reference_number' => $orderReferenceNumber,
                                'store_code' => $storeCode,
                                'store_name' => $storeName,
                                'delivery_date' => $deliveryDate,
                                'delivery_type' => $deliveryType,
                                'order_date' => date('Y-m-d', strtotime($orderDate)),
                                'item_code' => $orderedItems['item_code'],
                                'item_description' => $orderedItems['item_description'],
                                'item_category_name' => $orderedItems['item_category_name'],
                                'order_quantity' => $orderedItems['order_quantity'],
                                'allocated_quantity' => $orderedItems['allocated_quantity'],
                                'fan_out_category' => $orderedItems['fan_out_category'] ?? null,
                                'order_session_id' => $orderSessionId,
                                'store_sub_unit_short_name' => $storeSubUnitShortName,
                                'store_sub_unit_long_name' => $storeSubUnitLongName,
                                'received_quantity' => 0,
                                'received_items' => json_encode([]),
                                'type' => 0, // Order
                                'goods_issue_uuid' => $generateUniqueId,
                                'created_by_id' => $createdById,
                                'created_by_name' => $createdByName,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            $sapGoodsIssueData[$generateUniqueId] = [
                                'sr_inventory_item_id' => null, // To be updated after inserting StoreReceivingInventoryItem
                                'gi_id' => $storeOrders['gi_id'],
                                'gi_material_doc_year' => $storeOrders['gi_material_doc_year'],
                                'gi_material_doc' => $storeOrders['gi_material_doc'],
                                'gi_posting_date' => date('Y-m-d', strtotime($storeOrders['gi_posting_date'])),
                                'gi_inventory_stock_type' => $storeOrders['gi_inventory_stock_type'],
                                'gi_inventory_trans_type' => $storeOrders['gi_inventory_trans_type'],
                                'gi_batch' => $storeOrders['gi_batch'],
                                'gi_shelf_life_exp_date' => date('Y-m-d', strtotime($storeOrders['gi_shelf_life_exp_date'])),
                                'gi_manu_date' => date('Y-m-d', strtotime($storeOrders['gi_manu_date'])),
                                'gi_goods_movement_type' => $storeOrders['gi_goods_movement_type'],
                                'gi_purchase_order' => $storeOrders['gi_purchase_order'],
                                'gi_purchase_order_item' => $storeOrders['gi_purchase_order_item'],
                                'gi_entry_unit' => $storeOrders['gi_entry_unit'],
                                'gi_supplying_plant' => $storeOrders['gi_supplying_plant'],
                                'created_by_id' => $createdById,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
                // Bulk insert to speed up
                if (!empty($insertData)) {
                    StoreReceivingInventoryItemModel::insert($insertData);

                    // Find the goods issue uuid
                    $currentlyStoredItems = StoreReceivingInventoryItemModel::whereIn('goods_issue_uuid', array_keys($sapGoodsIssueData))->get();
                    foreach ($currentlyStoredItems as $storedItems) {
                        $sapGoodsIssueData[$storedItems->goods_issue_uuid]['sr_inventory_item_id'] = $storedItems->id;
                    }
                    StoreReceivingGoodsIssueItemModel::insert(array_values($sapGoodsIssueData));
                }
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.record_found'), $collatedData);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    private function collateGoodsIssueData($data)
    {
        $collatedData = [];
        foreach ($data as $item) {
            $deliveryDate = $item['DocumentDate'];
            $warehouseCode = $item['StorageLocation'];
            $collatedDataKey = "$deliveryDate|$warehouseCode";
            $deliveryType = $item['YY1_DeliveryTypePO_PDI'];
            $postingDate = $item['PostingDate'];
            if (!isset($collatedData[$collatedDataKey])) {
                $collatedData[$collatedDataKey] = [
                    'warehouse_code' => $warehouseCode,
                    'warehouse_name' => $item['YY1_Warehouse_Name_PDH'],
                    'delivery_date' => $deliveryDate,
                    'delivery_type' => $deliveryType,
                    'sessions' => [],

                    // SAP FIELDS
                    'gi_posting_date' => $postingDate,
                    'gi_plant' => $item['Plant'],
                ];
            }
            $storeCode = $item['ReceivingPlant'];
            $storeName = $item['YY1_Store_Name_PDI'];
            $storeSubUnitShortName = $item['YY1_Store_SubUnitPO1_PDI'];

            $sessionKey = "$storeCode|$storeSubUnitShortName";
            if (!isset($collatedData[$collatedDataKey]['sessions'][$sessionKey])) {
                $collatedData[$collatedDataKey]['sessions'][$sessionKey] = [
                    'store_code' => $storeCode,
                    'store_name' => $storeName,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'store_sub_unit_long_name' => $storeSubUnitShortName,
                    'delivery_date' => $deliveryDate,
                    'delivery_type' => $deliveryType,
                    'order_date' => $item['PurchaseOrderDate'],
                    'order_session_id' => $item['YY1_Order_Session_Id_PDI'],
                    'ordered_items' => [],

                    // SAP FIELDS
                    'gi_id' => $item['ID'],
                    'gi_material_doc_year' => $item['MaterialDocumentYear'],
                    'gi_material_doc' => $item['MaterialDocument'],
                    'gi_posting_date' => $postingDate,
                    'gi_inventory_stock_type' => $item['InventoryStockType'],
                    'gi_inventory_trans_type' => $item['InventoryTransactionType'],
                    'gi_batch' => $item['Batch'],
                    'gi_shelf_life_exp_date' => $item['ShelfLifeExpirationDate'],
                    'gi_manu_date' => $item['ManufactureDate'],
                    'gi_goods_movement_type' => $item['GoodsMovementType'],
                    'gi_purchase_order' => $item['PurchaseOrder'],
                    'gi_purchase_order_item' => $item['PurchaseOrderItem'],
                    'gi_entry_unit' => $item['EntryUnit'],
                    'gi_supplying_plant' => $item['SupplyingPlant'],
                ];
            }
            $orderedQuantity = (float) $item['QuantityInEntryUnit'];
            $collatedData[$collatedDataKey]['sessions'][$sessionKey]['ordered_items'][] = [
                'item_code' => $item['Material'],
                'item_description' => $item['YY1_Item_Description_PDI'],
                'item_category_name' => $item['YY1_Item_Category_Name_PDI'],
                'gi_entry_unit' => $item['EntryUnit'],
                'order_quantity' => $orderedQuantity,
                'allocated_quantity' => $orderedQuantity,
                'fan_out_category' => $item['YY1_Fan_Out_Category_MMI'] ?? null,
                'order_type' => $item['YY1_OrderType_PDI'] == '' ? null : $item['YY1_OrderType_PDI'],
            ];
        }
        return array_values($collatedData);
    }
    // --------------------------------------------------------------------


    // --------------------- Non SAP Functions ----------------------------
    public function onCreate(Request $request, $is_internal = false)
    {
        $fields = $request->validate([
            'created_by_name' => 'required',
            'created_by_id' => 'required',
            'consolidated_data' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $createdByName = $fields['created_by_name'];
            $createdById = $fields['created_by_id'];
            // if (!$is_internal) {
            //     $response = Http::withToken($request->bearerToken())
            //         ->get(env('MGIOS_URL') . '/check-token');
            //     if (!$response->successful()) {
            //         return $this->dataResponse('error', 404, 'Unauthorized Access');
            //     }
            // }

            // Decode consolidated data and insert them into the store_receiving_inventory table
            $consolidatedData = json_decode($fields['consolidated_data'], true);
            $consolidatedOrderId = $consolidatedData['consolidated_order_id'];
            $warehouseCode = $consolidatedData['warehouse_code'];
            $insertData = [];

            $generatedReferenceNumber = StoreReceivingInventoryModel::onGenerateReferenceNumber($consolidatedOrderId);
            $storeReceivingInventory = StoreReceivingInventoryModel::create([
                'consolidated_order_id' => $consolidatedOrderId,
                'warehouse_code' => $warehouseCode,
                'warehouse_name' => $consolidatedData['warehouse_name'],
                'reference_number' => $generatedReferenceNumber,
                'delivery_date' => $consolidatedData['delivery_date'],
                'delivery_type' => $consolidatedData['delivery_type'],
                'is_sap_created' => 0,
                'created_by_name' => $createdByName,
                'created_by_id' => $createdById,
                'updated_by_id' => $createdById,
            ]);

            foreach ($consolidatedData['sessions'] as $storeOrders) {
                $storeCode = $storeOrders['store_code'];
                $storeName = $storeOrders['store_name'];
                $deliveryDate = $storeOrders['delivery_date'];
                $deliveryType = $storeOrders['delivery_type'];
                $orderDate = $storeOrders['order_date'];
                $orderSessionId = $storeOrders['order_session_id'] ?? null;
                // $storeSubUnitId = $storeOrders['store_sub_unit_id'];
                $storeSubUnitShortName = $storeOrders['store_sub_unit_short_name'];
                $storeSubUnitLongName = $storeOrders['store_sub_unit_long_name'];
                $orderReferenceNumber = isset($storeOrders['order_session_id']) ? 'CO-' . $storeOrders['order_session_id'] : $storeOrders['reference_number'];

                $exists = StoreReceivingInventoryItemModel::where('reference_number', $orderReferenceNumber)->exists();

                if ($exists) {
                    throw new Exception('Reference number already exists: ' . $orderReferenceNumber);
                }
                if (isset($storeOrders['ordered_items'])) {
                    foreach ($storeOrders['ordered_items'] as $orderedItems) {
                        $insertData[] = [
                            'store_receiving_inventory_id' => $storeReceivingInventory->id,
                            'order_type' => $orderedItems['order_type'] ?? 0,
                            'reference_number' => $orderReferenceNumber,
                            'store_code' => $storeCode,
                            'store_name' => $storeName,
                            'delivery_date' => $deliveryDate,
                            'delivery_type' => $deliveryType,
                            'order_date' => $orderDate,
                            'item_code' => $orderedItems['item_code'],
                            'item_description' => $orderedItems['item_description'],
                            'item_category_name' => $orderedItems['item_category_name'],
                            'order_quantity' => $orderedItems['order_quantity'],
                            'allocated_quantity' => $orderedItems['allocated_quantity'],
                            'fan_out_category' => $orderedItems['fan_out_category'] ?? null,
                            'order_session_id' => $orderSessionId,
                            // 'store_sub_unit_id' => $storeSubUnitId,
                            'store_sub_unit_short_name' => $storeSubUnitShortName,
                            'store_sub_unit_long_name' => $storeSubUnitLongName,
                            'received_quantity' => 0,
                            'received_items' => json_encode([]),
                            'type' => $consolidatedData['movement_type'] ?? 0, // Order
                            'created_by_id' => $createdById,
                            'created_by_name' => $createdByName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Bulk insert to speed up
            if (!empty($insertData)) {
                StoreReceivingInventoryItemModel::insert($insertData);
                Http::post(config('apiurls.mgios.url') . config('apiurls.mgios.store_inventory_data_update') . $consolidatedOrderId);
            }

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }
    public function onGetById($store_receiving_inventory_id)
    {
        try {
            $storeReceivingInventoryModel = StoreReceivingInventoryModel::findOrFail($store_receiving_inventory_id);
            $data = [
                'reference_number' => $storeReceivingInventoryModel->reference_number,
                'store_inventory_items' => $storeReceivingInventoryModel->storeReceivingInventoryItems
            ];
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetCurrent($status, $store_code = null)
    {
        try {
            $storeReceivingInventoryItemModel = StoreReceivingInventoryItemModel::with('storeReceivingInventory')
                ->select([
                    'store_receiving_inventory_id',
                    'delivery_date',
                    'delivery_type',
                    DB::raw('COUNT(*) as total_items'),
                    'status',
                ])
                ->where('status', $status);

            if ($store_code !== null) {
                $storeReceivingInventoryItemModel->where('store_code', $store_code);
            }

            $storeReceivingInventoryItemModel = $storeReceivingInventoryItemModel->groupBy([
                'store_receiving_inventory_id',
                'delivery_date',
                'delivery_type',
                'status',
            ])->orderBy('store_receiving_inventory_id', 'DESC')->get();

            if (count($storeReceivingInventoryItemModel) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $storeReceivingInventoryItemModel);
            }

            return $this->dataResponse('error', 200, __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
