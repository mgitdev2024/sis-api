<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Models\Stock\StockTransferModel;
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
            'type' => 'required|in:pullout,store',
            'transfer_to_store_code' => 'required_if:type,store',
            'transfer_to_store_name' => 'required_if:type,store',
            'transfer_to_store_sub_unit_short_name' => 'required_if:type,store',
            'transportation_type' => 'required_if:type,store|in:1,2', // 1: Logistics, 2: Third Party
            'proof_of_booking' => 'required_if:transportation_type,2',
            'created_by_id' => 'required',

            // Store Details
            'store_code' => 'required|string',
            'store_sub_unit_short_name' => 'required|string'
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
            $filepath = null;

            if ($fields['proof_of_booking'] != null) {
                $attachmentPath = $request->file('proof_of_booking')->store('public/attachments/stock_transfer');
                $filepath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);
            }
            $createdById = $fields['created_by_id'];

            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];
            $referenceNumber = StockTransferModel::generateReferenceNumber($type);

            $stockTransferModel = StockTransferModel::create([
                'reference_number' => $referenceNumber,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'transfer_type' => ($type == 'pullout') ? 1 : 0, // 0 = Store Transfer, 1 = Pull Out
                'transportation_type' => $transportationType,
                'pickup_date' => $pickupDate,
                'location_code' => $transferToStoreCode,
                'location_name' => $transferToStoreName,
                'location_sub_unit' => $transferToStoreSubUnitShortName,
                'remarks' => $remarks,
                'attachment' => $filepath,
                'created_by_id' => $createdById,
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

            // TODO If Store, create a receiving ticket for them to receive the items tobe continued
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, __('msg.create_failed'), $exception->getMessage());
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
            $stockTransfer = StockTransferModel::with('StockTransferItems')->findOrFail($id);
            return $this->dataResponse('success', 200, __('msg.record_found'), $stockTransfer);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
