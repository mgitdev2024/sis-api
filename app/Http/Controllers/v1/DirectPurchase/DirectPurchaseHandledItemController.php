<?php

namespace App\Http\Controllers\v1\DirectPurchase;

use App\Http\Controllers\Controller;
use App\Models\DirectPurchase\DirectPurchaseHandledItemModel;
use App\Models\DirectPurchase\DirectPurchaseItemModel;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use DB;
class DirectPurchaseHandledItemController extends Controller
{
    use ResponseTrait;

    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|in:0,1', // 0 = rejected, 1 = received
            'direct_purchase_item_id' => 'required|exists:direct_purchase_items,id',
            'delivery_receipt_number' => 'nullable',
            'received_date' => 'required',
            'quantity' => 'required|integer',
            'remarks' => 'nullable',
            'created_by_id' => 'required'
        ]);

        try {
            DB::beginTransaction();
            $type = $fields['type'];
            $directPurchaseItemId = $fields['direct_purchase_item_id'];
            $deliveryReceiptNumber = $fields['delivery_receipt_number'] ?? null;
            $quantity = $fields['quantity'];
            $remarks = $fields['remarks'] ?? null;
            $createdByid = $fields['created_by_id'];
            $receivedDate = $fields['received_date'];
            $expirationDate = null;

            $directPurchaseItemModel = DirectPurchaseItemModel::find($directPurchaseItemId);
            $requestedQuantity = $directPurchaseItemModel->requested_quantity;
            $totalReceivedQuantity = $directPurchaseItemModel->total_received_quantity;
            $remainingQuantity = $requestedQuantity - $totalReceivedQuantity;
            $itemCode = $directPurchaseItemModel->item_code;
            if ($quantity > $remainingQuantity) {
                throw new Exception('Total Quantity cannot exceed to the requested quantity');
            }

            // Get Expiration Date check-item-code/
            $response = \Http::get(config('apiurls.mgios.url') . config('apiurls.mgios.check_item_code') . $itemCode);
            if ($response->successful()) {
                $shelfLifeDays = $response->json()['item_base']['shelf_life_days'] ?? 0;
                $expirationDate = date('Y-m-d', strtotime("+$shelfLifeDays days", strtotime($receivedDate)));
            }

            DirectPurchaseHandledItemModel::create([
                'type' => $type,
                'direct_purchase_item_id' => $directPurchaseItemId,
                'delivery_receipt_number' => $deliveryReceiptNumber,
                'received_date' => $receivedDate,
                'expiration_date' => $expirationDate,
                'quantity' => $quantity,
                'remarks' => $remarks,
                'storage' => 'default',
                'created_by_id' => $createdByid
            ]);

            if ($type == 1) {
                $directPurchaseItemModel->total_received_quantity += $quantity;
                $directPurchaseItemModel->save();
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onUpdate(Request $request, $direct_purchase_handled_item_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'updated_quantity' => 'required',
            'delivery_receipt_number' => 'required',
            'received_date' => 'required',
            'expiration_date' => 'nullable',
            'remarks' => 'nullable',
        ]);
        try {
            DB::beginTransaction();
            $createdByid = $fields['created_by_id'];
            $updatedQuantity = $fields['updated_quantity'];
            $deliveryReceiptNumber = $fields['delivery_receipt_number'];
            $receivedDate = $fields['received_date'];
            $remarks = $fields['remarks'] ?? null;
            $expirationDate = $fields['expiration_date'] ?? null;
            $directPurchaseHandledItemModel = DirectPurchaseHandledItemModel::find($direct_purchase_handled_item_id);
            if ($directPurchaseHandledItemModel) {

                $toBeDeducted = $directPurchaseHandledItemModel->quantity;

                $directPurchaseHandledItemModel->update([
                    'delivery_receipt_number' => $deliveryReceiptNumber,
                    'received_date' => $receivedDate,
                    'quantity' => $updatedQuantity,
                    'expiration_date' => $expirationDate,
                    'remarks' => $remarks,
                    'updated_by_id' => $createdByid,
                    'updated_at' => now()
                ]);

                if ($directPurchaseHandledItemModel->type == 1) {
                    $directPurchaseItemModel = $directPurchaseHandledItemModel->directPurchaseItem;
                    $totalReceivedQuantity = ($directPurchaseItemModel->total_received_quantity - $toBeDeducted) + $updatedQuantity;
                    $directPurchaseItemModel->total_received_quantity = $totalReceivedQuantity;
                    $directPurchaseItemModel->save();
                }
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'));
            } else {
                return $this->dataResponse('error', 404, __('msg.update_failed'));
            }
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onDelete($direct_purchase_handled_item_id)
    {
        try {
            DB::beginTransaction();
            $directPurchaseHandledItemModel = DirectPurchaseHandledItemModel::find($direct_purchase_handled_item_id);
            if ($directPurchaseHandledItemModel) {
                if ($directPurchaseHandledItemModel->type == 1) {
                    $directPurchaseItemModel = $directPurchaseHandledItemModel->directPurchaseItem;
                    $directPurchaseItemModel->total_received_quantity -= $directPurchaseHandledItemModel->quantity;
                    $directPurchaseItemModel->save();
                }
                $directPurchaseHandledItemModel->delete();
                DB::commit();

                return $this->dataResponse('success', 200, __('msg.delete_success'));
            } else {
                return $this->dataResponse('error', 404, __('msg.delete_failed'));
            }
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.delete_failed'), $exception->getMessage());
        }
    }

    public function onPost(Request $request, $direct_purchase_handled_item_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $directPurchaseHandledItemModel = DirectPurchaseHandledItemModel::find($direct_purchase_handled_item_id);
            if ($directPurchaseHandledItemModel) {
                $directPurchaseHandledItemModel->status = 1;
                $directPurchaseHandledItemModel->updated_by_id = $createdById;
                $directPurchaseHandledItemModel->save();

                $directPurchaseItemModel = $directPurchaseHandledItemModel->directPurchaseItem;
                $directPurchaseModel = $directPurchaseItemModel->directPurchase;

                $referenceNumber = $directPurchaseModel->reference_number;
                $storeCode = $directPurchaseModel->store_code;
                $storeSubUnitShortName = $directPurchaseModel->store_sub_unit_short_name;
                $itemCode = $directPurchaseItemModel->item_code;
                $itemDescription = $directPurchaseItemModel->item_description;
                $itemCategoryName = $directPurchaseItemModel->item_category_name;
                $quantity = $directPurchaseHandledItemModel->quantity;


                $this->onUpdateStockInventory(
                    $itemCode,
                    $itemDescription,
                    $itemCategoryName,
                    $storeCode,
                    $storeSubUnitShortName,
                    $quantity,
                    $createdById
                );

                $this->onUpdateStockLog(
                    $storeCode,
                    $storeSubUnitShortName,
                    $itemCode,
                    $itemDescription,
                    $itemCategoryName,
                    $quantity,
                    $createdById,
                    $referenceNumber
                );
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'));
            } else {
                return $this->dataResponse('error', 404, __('msg.update_failed'));
            }
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onUpdateStockInventory($itemCode, $itemDescription, $itemCategoryName, $storeCode, $storeSubUnitShortName, $quantity, $createdById)
    {
        try {
            $stockInventoryModel = StockInventoryModel::where([
                'item_code' => $itemCode,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
            ])->first();

            if ($stockInventoryModel) {
                $stockInventoryModel->stock_count += $quantity;
                $stockInventoryModel->updated_by_id = $createdById;
                $stockInventoryModel->save();
            } else {
                StockInventoryModel::create([
                    'item_code' => $itemCode,
                    'item_description' => $itemDescription,
                    'item_category_name' => $itemCategoryName,
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'stock_count' => $quantity,
                    'created_by_id' => $createdById,
                ]);
            }
        } catch (Exception $exception) {
            throw new Exception('Failed to update stock inventory');
        }
    }

    public function onUpdateStockLog($storeCode, $storeSubUnitShortName, $itemCode, $itemDescription, $itemCategoryName, $quantity, $createdById, $referenceNumber)
    {
        $stockLogModel = StockLogModel::where([
            'store_code' => $storeCode,
            'store_sub_unit_short_name' => $storeSubUnitShortName,
            'item_code' => $itemCode,
        ])->orderBy('id', 'DESC')->first();

        $stockLogModelNew = new StockLogModel();
        $stockLogModelNew->create([
            'reference_number' => $referenceNumber,
            'store_code' => $storeCode,
            'store_sub_unit_short_name' => $storeSubUnitShortName,
            'item_code' => $itemCode,
            'item_description' => $itemDescription,
            'item_category_name' => $itemCategoryName,
            'quantity' => $quantity,
            'initial_stock' => $stockLogModel->final_stock ?? 0,
            'final_stock' => $quantity + ($stockLogModel->final_stock ?? 0),
            'transaction_type' => 'in',
            'created_by_id' => $createdById,
        ]);
    }

    public function onGetById($direct_purchase_handled_item_id)
    {
        try {
            $directPurchaseHandledItemModel = DirectPurchaseHandledItemModel::find($direct_purchase_handled_item_id);
            if ($directPurchaseHandledItemModel) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $directPurchaseHandledItemModel);
            } else {
                return $this->dataResponse('error', 404, __('msg.record_failed'));
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_failed'), $exception->getMessage());
        }
    }
}
