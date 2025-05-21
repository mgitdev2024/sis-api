<?php

namespace App\Http\Controllers\v1\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder\PurchaseOrderHandledItemModel;
use App\Models\PurchaseOrder\PurchaseOrderItemModel;
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
                'quantity' => $quantity,
                'remarks' => $remarks,
                'storage' => 'default',
                'created_by_id' => $createdByid
            ]);

            if ($type == 1) {
                $purchaseOrderItemModel->total_received_quantity += $quantity;
                $purchaseOrderItemModel->save();
            }
            // tentative expiration date
            // if ($type == 1) {
            //     $purchaseOrderItemModel = PurchaseOrderItemModel::find($purchaseOrderItemId);
            //     $itemCode = $purchaseOrderItemModel->item_code;
            //     $response = \Http::get(env('MGIOS_URL') . '/item-details/get/' . $itemCode);
            //     $response = $response->json() ?? null;
            //     $isPerishable = $response['item_base']['is_perishable'];
            //     $shelfLifeDays = 0;
            //     if ($isPerishable == 1) {
            //         $shelfLifeDays = $response['item_base']['shelf_life'];
            //     }
            // }
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
            'updated_quantity' => 'required'
        ]);
        try {

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }
}
