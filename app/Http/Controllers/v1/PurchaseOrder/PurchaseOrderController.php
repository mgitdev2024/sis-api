<?php

namespace App\Http\Controllers\v1\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder\PurchaseOrderItemModel;
use App\Models\PurchaseOrder\PurchaseOrderModel;
use App\Models\Stock\StockInventoryModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use DB;

class PurchaseOrderController extends Controller
{
    use ResponseTrait, CrudOperationsTrait;

    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'purchase_order_number' => 'required',
            'supplier_code' => 'required',
            'supplier_name' => 'required',
            'purchase_order_date' => 'required|date',
            'expected_delivery_date' => 'required|date',
            'purchase_order_items' => 'required|json', // [{"ic":"CR 12","q":12,"ict":"Breads","icd":"Cheeseroll Box of 12"}]
            'created_by_id' => 'required',
            'store_code' => 'required|string',
            'store_sub_unit_short_name' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $purchaseOrderNumber = $fields['purchase_order_number'];
            $supplierCode = $fields['supplier_code'];
            $supplierName = $fields['supplier_name'];
            $purchaseOrderDate = $fields['purchase_order_date'];
            $expectedDeliveryDate = $fields['expected_delivery_date'];
            $purchaseOrderItems = $fields['purchase_order_items'];
            $createdById = $fields['created_by_id'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'] ?? null;

            $purchaseOrderModel = PurchaseOrderModel::create([
                'reference_number' => $purchaseOrderNumber,
                'supplier_code' => $supplierCode,
                'supplier_name' => $supplierName,
                'purchase_order_date' => $purchaseOrderDate,
                'expected_delivery_date' => $expectedDeliveryDate,
                'created_by_id' => $createdById,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
            ]);

            $purchaseOrderItemsArr = $this->onCreatePurchaseOrderItems($purchaseOrderModel->id, $purchaseOrderItems, $createdById);

            $data = [
                'purchase_order_details' => $purchaseOrderModel,
                'purchase_order_items' => $purchaseOrderItemsArr
            ];
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'), $data);
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    private function onCreatePurchaseOrderItems($purchaseOrderId, $purchaseOrderItems, $createdById)
    {
        try {
            $purchaseOrderItems = json_decode($purchaseOrderItems, true);

            $data = [];
            foreach ($purchaseOrderItems as $items) {
                $itemCode = $items['ic'];
                $itemCategoryName = $items['ict'];
                $itemDescription = $items['icd'];
                $quantity = $items['q'];

                PurchaseOrderItemModel::create([
                    'purchase_order_id' => $purchaseOrderId,
                    'item_code' => $itemCode,
                    'item_description' => $itemCategoryName,
                    'item_category_name' => $itemDescription,
                    'total_received_quantity' => 0,
                    'requested_quantity' => $quantity,
                    'created_by_id' => $createdById
                ]);

                $data[] = [
                    'purchase_order_id' => $purchaseOrderId,
                    'item_code' => $itemCode,
                    'item_description' => $itemCategoryName,
                    'item_category_name' => $itemDescription,
                    'total_received_quantity' => 0,
                    'requested_quantity' => $quantity,
                    'created_by_id' => $createdById
                ];
            }


            return $data;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
    public function onGetCurrent($status, $purchase_order_id = 0, $store_code, $sub_unit = null)
    {
        $whereFields = [
            'status' => $status,
            'store_code' => $store_code,
        ];

        if ($sub_unit != null) {
            $whereFields['store_sub_unit_short_name'] = $sub_unit;
        }

        $withFunction = null;
        if ($purchase_order_id != 0) {
            $whereFields['id'] = $purchase_order_id;
            $withFunction = 'purchaseOrderItems.purchaseOrderHandledItems';
        }

        return $this->readCurrentRecord(PurchaseOrderModel::class, null, $whereFields, $withFunction, ['id' => 'DESC'], 'Purchase Order');
    }

    // public function onUpdateStockInventory($itemCode, $itemDescription, $itemCategoryName, $storeCode, $storeSubUnitShortName, $quantity, $createdById)
    // {
    //     try {
    //         $stockInventoryModel = StockInventoryModel::where([
    //             'item_code' => $itemCode,
    //             'store_code' => $storeCode,
    //             'store_sub_unit_short_name' => $storeSubUnitShortName,
    //         ])->first();

    //         if ($stockInventoryModel) {
    //             $stockInventoryModel->stock_count -= $quantity;
    //             $stockInventoryModel->updated_by_id = $createdById;
    //             $stockInventoryModel->save();
    //         } else {
    //             StockInventoryModel::create([
    //                 'item_code' => $itemCode,
    //                 'item_description' => $itemDescription,
    //                 'item_category_name' => $itemCategoryName,
    //                 'store_code' => $storeCode,
    //                 'store_sub_unit_short_name' => $storeSubUnitShortName,
    //                 'stock_count' => $quantity,
    //                 'created_by_id' => $createdById,
    //             ]);
    //         }
    //     } catch (Exception $exception) {
    //         throw new Exception('Failed to update stock inventory');
    //     }
    // }

    // public function onUpdateStockLog($storeCode, $storeSubUnitShortName, $itemCode, $itemDescription, $itemCategoryName, $quantity, $createdById, $referenceNumber)
    // {
    //     $stockLogModel = StockLogModel::where([
    //         'store_code' => $storeCode,
    //         'store_sub_unit_short_name' => $storeSubUnitShortName,
    //         'item_code' => $itemCode,
    //     ])->orderBy('id', 'DESC')->first();

    //     $stockLogModelNew = new StockLogModel();
    //     $stockLogModelNew->create([
    //         'reference_number' => $referenceNumber,
    //         'store_code' => $storeCode,
    //         'store_sub_unit_short_name' => $storeSubUnitShortName,
    //         'item_code' => $itemCode,
    //         'item_description' => $itemDescription,
    //         'item_category_name' => $itemCategoryName,
    //         'quantity' => $quantity,
    //         'initial_stock' => $stockLogModel->final_stock ?? 0,
    //         'final_stock' => $quantity + ($stockLogModel->final_stock ?? 0),
    //         'transaction_type' => 'in',
    //         'created_by_id' => $createdById,
    //     ]);
    // }
}
