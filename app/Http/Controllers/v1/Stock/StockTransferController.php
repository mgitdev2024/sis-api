<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\Store\StoreReceivingInventoryController;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Models\Stock\StockTransferModel;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use DB;
use Exception;
class StockTransferController extends Controller
{
    use ResponseTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'transfer_items' => 'required|json', // [{"ic":"CR 12","q":12,"ict":"Breads","icd":"Cheeseroll Box of 12"}]
            'pickup_date' => 'required|date',
            'remarks' => 'nullable|string',
            'type' => 'required|in:pullout,store,store_warehouse_store', // pullout, store, store_warehouse_store
            'transfer_to_store_code' => 'required_if:type,store',
            'transfer_to_store_name' => 'required_if:type,store',
            'transfer_to_store_sub_unit_short_name' => 'required_if:type,store',
            'transportation_type' => 'nullable|required_if:type,store|in:1,2,3', // 1: Logistics, 2: Third Party, 3 Store Staff
            'created_by_id' => 'required',

            // Store Details
            'store_code' => 'required|string',
            'store_sub_unit_short_name' => 'nullable|string'
        ]);
        try {

            DB::beginTransaction();
            $transferItems = $fields['transfer_items'];
            $pickupDate = $fields['pickup_date'];
            $remarks = $fields['remarks'] ?? '';
            $type = $fields['type'];
            $transferToStoreCode = $fields['transfer_to_store_code'] ?? null;
            $transferToStoreName = $fields['transfer_to_store_name'] ?? null;
            $transferToStoreSubUnitShortName = $fields['transfer_to_store_sub_unit_short_name'] ?? null;
            $transportationType = $fields['transportation_type'] ?? null;

            $createdById = $fields['created_by_id'];

            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'] ?? null;
            $referenceNumber = StockTransferModel::onGenerateReferenceNumber($type);

            $transferTypeArr = [
                'store' => 0, // 0 = Store Transfer
                'pullout' => 1, // 1 = Pull Out
                'store_warehouse_store' => 2 // 2 = Store Warehouse Store
            ];
            $stockTransferModel = StockTransferModel::create([
                'reference_number' => $referenceNumber,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'transfer_type' => $transferTypeArr[$type],
                'transportation_type' => $transportationType,
                'pickup_date' => $pickupDate,
                'location_code' => $transferToStoreCode,
                'location_name' => $transferToStoreName,
                'location_sub_unit' => $transferToStoreSubUnitShortName,
                'remarks' => $remarks,
                'created_by_id' => $createdById,
                // 'status' => ($type == 'pullout') ? 2 : 1, // 2 = received, 1 = For Receive
            ]);

            $stockTransferItemController = new StockTransferItemController();
            $stockTransferItemRequest = new Request([
                'stock_transfer_id' => $stockTransferModel->id,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'reference_number' => $referenceNumber,
                'transfer_items' => $transferItems,
                'created_by_id' => $createdById,
            ]);
            $stockTransferItemController->onCreate($stockTransferItemRequest);


            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onCreateStoreReceivingInventory($transferToStoreCode, $transferToStoreName, $transferToStoreSubUnitShortName, $pickupDate, $referenceNumber, $transferItems, $createdById)
    {
        try {
            $userModel = User::where('employee_id', $createdById)->first();

            $consolidatedData = [
                'consolidated_order_id' => $referenceNumber,
                'warehouse_code' => $transferToStoreCode,
                'warehouse_name' => $transferToStoreName,
                'reference_number' => $referenceNumber,
                'delivery_date' => $pickupDate,
                'delivery_type' => '1D',
                'created_by_name' => $userModel->first_name . ' ' . $userModel->last_name,
                'created_by_id' => $createdById,
                'updated_by_id' => $createdById,
                'sessions' => []

            ];
            $transferItems = json_decode($transferItems, true);
            $orderedItems = [];
            foreach ($transferItems as $item) {
                $orderedItems[] = [
                    'item_code' => $item['item_code'],
                    'item_description' => $item['item_description'],
                    'item_category_name' => $item['item_category_name'],
                    'order_quantity' => $item['quantity'],
                    'allocated_quantity' => $item['quantity'],
                    'order_type' => 0,
                ];
            }
            $storeReceivingInventoryController = new StoreReceivingInventoryController();
            $sessions = [
                'store_code' => $transferToStoreCode,
                'store_name' => $transferToStoreName,
                'date_created' => now(),
                'delivery_date' => $pickupDate,
                'delivery_type' => '1D',
                'order_date' => $pickupDate,
                'reference_number' => $referenceNumber,
                'store_sub_unit_short_name' => $transferToStoreSubUnitShortName,
                'store_sub_unit_long_name' => $transferToStoreSubUnitShortName == 'BOH' ? 'Back of the House' : 'Front of the House',
                'ordered_items' => $orderedItems,
            ];

            $consolidatedData['sessions'][] = $sessions;
            $storeReceivingInventoryRequest = new Request([
                'created_by_name' => $userModel->first_name . ' ' . $userModel->last_name,
                'created_by_id' => $createdById,
                'consolidated_data' => json_encode($consolidatedData)
            ]);
            $storeReceivingInventoryController->onCreate($storeReceivingInventoryRequest, true);

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
            // return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onPickUpTransfer(Request $request, $id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'attachment' => 'required',
        ]);
        try {
            $filepath = null;
            $stockTransferModel = StockTransferModel::findOrFail($id);
            $type = $stockTransferModel->transfer_type; // 0 = Store Transfer, 1 = Pull Out, 2 = Store Warehouse Store
            $transferItems = json_encode($stockTransferModel->StockTransferItems);
            $transferToStoreCode = $stockTransferModel->location_code;
            $transferToStoreName = $stockTransferModel->location_name;
            $transferToStoreSubUnitShortName = $stockTransferModel->location_sub_unit;
            $pickupDate = $stockTransferModel->pickup_date;
            $referenceNumber = $stockTransferModel->reference_number;
            $remarks = $stockTransferModel->remarks;
            $createdById = $fields['created_by_id'];

            DB::beginTransaction();
            if (isset($fields['attachment']) && $fields['attachment'] != null) {
                $attachmentPath = $request->file('attachment')->store('public/attachments/stock_transfer');
                $filepath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);
                $stockTransferModel->attachment = $filepath;
            }
            $stockTransferModel->logistics_picked_up_at = now();
            $stockTransferModel->logistics_confirmed_by_id = $createdById;

            if ($type == 0) {
                $this->onCreateStoreReceivingInventory($transferToStoreCode, $transferToStoreName, $transferToStoreSubUnitShortName, $pickupDate, $referenceNumber, $transferItems, $createdById);
            } else if ($type == 1) {
                $stockTransferModel->status = 2; // Received
                $this->onCreateTransmittalPullout($transferItems, $createdById, $remarks);
            } else if ($type == 2) {
                $this->onCreateStoreWarehouseStoreReceivingInventory($stockTransferModel, $transferItems, $createdById);
            }
            $stockTransferModel->save();

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());

        }
    }

    public function onCreateTransmittalPullout($transferItems, $createdById, $remarks)
    {

        try {
            $userModel = User::where('employee_id', $createdById)->first();
            $firstName = $userModel->first_name;
            $lastName = $userModel->last_name;
            $fullName = "$firstName $lastName";
            $data = [
                'pullout_items' => $transferItems,
                'created_by_name' => $fullName,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'remarks' => $remarks
            ];

            // api call for stock adjustment
            $response = \Http::post(env('MGIOS_URL') . '/stock-adjustment/create', $data);
            if (!$response->successful()) {
                return $this->dataResponse('error', 404, 'Unauthorized Access');
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onCreateStoreWarehouseStoreReceivingInventory($stockTransferModel, $transferItems, $createdById)
    {
        try {
            $userModel = User::where('employee_id', $createdById)->first();
            $firstName = $userModel->first_name;
            $lastName = $userModel->last_name;
            $fullName = "$firstName $lastName";

            $referenceNumber = $stockTransferModel->reference_number;
            $storeCode = $stockTransferModel->store_code;
            $storeSubUnitShortName = $stockTransferModel->store_sub_unit_short_name;
            $pickupDate = $stockTransferModel->pickup_date;
            $transferToStoreCode = $stockTransferModel->location_code;
            $transferToStoreName = $stockTransferModel->location_name;
            $transferToStoreSubUnitShortName = $stockTransferModel->location_sub_unit;
            $remarks = $stockTransferModel->remarks;
            $transferType = $stockTransferModel->transfer_type;
            $transportationType = $stockTransferModel->transportation_type;
            $data = [
                'sis_stock_transfer_id' => $stockTransferModel->id,
                'reference_number' => $referenceNumber,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'transfer_items' => $transferItems,
                'transfer_type' => $transferType,
                'pickup_date' => $pickupDate,
                'location_code' => $transferToStoreCode,
                'location_name' => $transferToStoreName,
                'location_sub_unit' => $transferToStoreSubUnitShortName ?? null,
                'transportation_type' => $transportationType,
                'created_by_name' => $fullName,
                'remarks' => $remarks
            ];

            // api call for transmittal
            $response = \Http::post(env('MGIOS_URL') . '/receiving/stock-transfer/create', $data);
            if (!$response->successful()) {
                return $this->dataResponse('error', 404, 'Unauthorized Access');
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
    public function onCancel($id)
    {
        $fields = request()->validate([
            'created_by_id' => 'required|string',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $stockTransfer = StockTransferModel::findOrFail($id);
            $stockTransfer->status = 0; // 0 = Cancelled
            $stockTransfer->save();

            $stockTransferItems = $stockTransfer->StockTransferItems;
            foreach ($stockTransferItems as $item) {
                // Return Stock Quantity if cancelled
                $currentStockLogModel = StockLogModel::where([
                    'store_code' => $stockTransfer->store_code,
                    'store_sub_unit_short_name' => $stockTransfer->store_sub_unit_short_name,
                    'item_code' => $item->item_code,
                ])
                    ->orderBy('id', 'DESC')->first();
                $stockLogModel = new StockLogModel();
                $stockLogModel->item_code = $currentStockLogModel->item_code;
                $stockLogModel->item_description = $currentStockLogModel->item_description;
                $stockLogModel->item_category_name = $currentStockLogModel->item_category_name;
                $stockLogModel->quantity = $currentStockLogModel->quantity;
                $stockLogModel->store_code = $currentStockLogModel->store_code;
                $stockLogModel->store_sub_unit_short_name = $currentStockLogModel->store_sub_unit_short_name;
                $stockLogModel->reference_number = $currentStockLogModel->reference_number;
                $stockLogModel->transaction_type = 'in';
                $stockLogModel->transaction_sub_type = 'returned';
                $stockLogModel->initial_stock = $currentStockLogModel->final_stock;
                $stockLogModel->final_stock = $currentStockLogModel->final_stock + $currentStockLogModel->quantity;
                $stockLogModel->created_by_id = $createdById;
                $stockLogModel->created_at = now();
                $stockLogModel->save();

                $stockInventoryModel = StockInventoryModel::where('store_code', $stockTransfer->store_code)
                    ->where('store_sub_unit_short_name', $stockTransfer->store_sub_unit_short_name)
                    ->where('item_code', $currentStockLogModel->item_code)
                    ->first();
                $stockInventoryModel->stock_count += $currentStockLogModel->quantity;
                $stockInventoryModel->updated_by_id = $createdById;
                $stockInventoryModel->updated_at = now();
                $stockInventoryModel->save();
            }

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 500, __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onGet($status, $store_code, $sub_unit = null)
    {
        try {
            $query = StockTransferModel::query();
            if ($status == 'all') {
                $query->where('store_code', $store_code);
            } else {
                $query->where('store_code', $store_code)->where('status', $status);
            }
            if ($sub_unit) {
                $query->where('store_sub_unit_short_name', $sub_unit);
            }
            $stockTransfers = $query->get();
            return $this->dataResponse('success', 200, __('msg.record_found'), $stockTransfers);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetById($id)
    {
        try {
            $stockTransfer = StockTransferModel::with('StockTransferItems')->find($id);
            if ($stockTransfer == null) {
                return $this->dataResponse('error', 200, __('msg.record_not_found'));

            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $stockTransfer);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onUpdate(Request $request, $id)
    {
        $fields = $request->validate([
            'warehouse_received_by_name' => 'required|string',
            'type' => 'nullable', // pullout, store_warehouse_store
        ]);

        try {
            DB::beginTransaction();
            $type = $fields['type'] ?? null;
            $stockTransferModel = StockTransferModel::findOrFail($id);
            $stockTransferModel->warehouse_received_by_name = $fields['warehouse_received_by_name'];
            $stockTransferModel->warehouse_received_at = now();
            $stockTransferModel->status = 2; // Receive
            $stockTransferModel->updated_at = now();
            $stockTransferModel->save();

            if ($type == 'store_warehouse_store') {
                $referenceNumber = $stockTransferModel->reference_number;
                $pickupDate = $stockTransferModel->pickup_date;
                $transferToStoreCode = $stockTransferModel->location_code;
                $transferToStoreName = $stockTransferModel->location_name;
                $transferToStoreSubUnitShortName = $stockTransferModel->location_sub_unit;
                $createdById = $stockTransferModel->created_by_id;
                $transferItems = json_encode($stockTransferModel->StockTransferItems);
                // Create Store Receiving Inventory
                $this->onCreateStoreReceivingInventory($transferToStoreCode, $transferToStoreName, $transferToStoreSubUnitShortName, $pickupDate, $referenceNumber, $transferItems, $createdById);
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());

        }
    }
}
