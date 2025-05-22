<?php

namespace App\Http\Controllers\v1\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder\PurchaseOrderHandledItemModel;
use App\Models\PurchaseOrder\PurchaseOrderItemModel;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use DB;
class PurchaseOrderHandledItemController extends Controller
{
    use ResponseTrait;

    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|in:0,1', // 0 = rejected, 1 = received
            'purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'delivery_receipt_number' => 'required',
            'received_date' => 'required',
            'expiration_date' => 'nullable',
            'quantity' => 'required|integer',
            'remarks' => 'nullable',
            'created_by_id' => 'required'
        ]);

        try {
            DB::beginTransaction();
            $type = $fields['type'];
            $purchaseOrderItemId = $fields['purchase_order_item_id'];
            $deliveryReceiptNumber = $fields['delivery_receipt_number'];
            $quantity = $fields['quantity'];
            $remarks = $fields['remarks'] ?? null;
            $createdByid = $fields['created_by_id'];
            $receivedDate = $fields['received_date'];
            $expirationDate = $fields['expiration_date'] ?? null;

            $purchaseOrderItemModel = PurchaseOrderItemModel::find($purchaseOrderItemId);
            $requestedQuantity = $purchaseOrderItemModel->requested_quantity;
            $totalReceivedQuantity = $purchaseOrderItemModel->total_received_quantity;
            $remainingQuantity = $requestedQuantity - $totalReceivedQuantity;

            if ($quantity > $remainingQuantity) {
                throw new Exception('Total Quantity cannot exceed to the requested quantity');
            }
            PurchaseOrderHandledItemModel::create([
                'type' => $type,
                'purchase_order_item_id' => $purchaseOrderItemId,
                'delivery_receipt_number' => $deliveryReceiptNumber,
                'received_date' => $receivedDate,
                'expiration_date'=>$expirationDate,
                'quantity' => $quantity,
                'remarks' => $remarks,
                'storage' => 'default',
                'created_by_id' => $createdByid
            ]);

            if ($type == 1) {
                $purchaseOrderItemModel->total_received_quantity += $quantity;
                $purchaseOrderItemModel->save();
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onUpdate(Request $request, $purchase_order_handled_item_id)
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
            $purchaseOrderHandledItemModel = PurchaseOrderHandledItemModel::find($purchase_order_handled_item_id);
            if ($purchaseOrderHandledItemModel) {

                $toBeDeducted = $purchaseOrderHandledItemModel->quantity;

                $purchaseOrderHandledItemModel->update([
                    'delivery_receipt_number' => $deliveryReceiptNumber,
                    'received_date' => $receivedDate,
                    'quantity' => $updatedQuantity,
                    'expiration_date' => $expirationDate,
                    'remarks' => $remarks,
                    'updated_by_id' => $createdByid,
                    'updated_at' => now()
                ]);

                if ($purchaseOrderHandledItemModel->type == 1) {
                    $purchaseOrderItemModel = $purchaseOrderHandledItemModel->purchaseOrderItem;
                    $totalReceivedQuantity = ($purchaseOrderItemModel->total_received_quantity - $toBeDeducted) + $updatedQuantity;
                    $purchaseOrderItemModel->total_received_quantity = $totalReceivedQuantity;
                    $purchaseOrderItemModel->save();
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

    public function onDelete($purchase_order_handled_item_id)
    {
        try {
            DB::beginTransaction();
            $purchaseOrderHandledItemModel = PurchaseOrderHandledItemModel::find($purchase_order_handled_item_id);
            if ($purchaseOrderHandledItemModel) {
                if ($purchaseOrderHandledItemModel->type == 1) {
                    $purchaseOrderItemModel = $purchaseOrderHandledItemModel->purchaseOrderItem;
                    $purchaseOrderItemModel->total_received_quantity -= $purchaseOrderHandledItemModel->quantity;
                    $purchaseOrderItemModel->save();
                }
                $purchaseOrderHandledItemModel->delete();
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

    public function onPost(Request $request, $purchase_order_handled_item_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $purchaseOrderHandledItemModel = PurchaseOrderHandledItemModel::find($purchase_order_handled_item_id);
            if ($purchaseOrderHandledItemModel) {
                $purchaseOrderHandledItemModel->status = 1;
                $purchaseOrderHandledItemModel->updated_by_id = $createdById;
                $purchaseOrderHandledItemModel->save();

                $purchaseOrderItemModel = $purchaseOrderHandledItemModel->purchaseOrderItem;
                $purchaseOrderModel = $purchaseOrderItemModel->purchaseOrder;

                $referenceNumber = $purchaseOrderModel->reference_number;
                $storeCode = $purchaseOrderModel->store_code;
                $storeSubUnitShortName = $purchaseOrderModel->store_sub_unit_short_name;
                $itemCode = $purchaseOrderItemModel->item_code;
                $itemDescription = $purchaseOrderItemModel->item_description;
                $itemCategoryName = $purchaseOrderItemModel->item_category_name;
                $quantity = $purchaseOrderHandledItemModel->quantity;


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
}
