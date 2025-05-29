<?php

namespace App\Http\Controllers\v1\DirectPurchase;

use App\Http\Controllers\Controller;
use App\Models\DirectPurchase\DirectPurchaseItemModel;
use App\Models\DirectPurchase\DirectPurchaseModel;
use App\Models\Stock\StockInventoryModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use DB;

class DirectPurchaseController extends Controller
{
    use ResponseTrait, CrudOperationsTrait;

    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required|in:0,1', // 0 = DR, 1 = PO
            'direct_reference_number' => 'required',
            'supplier_code' => 'nullable',
            'supplier_name' => 'required',
            'direct_purchase_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'direct_purchase_items' => 'required|json', // [{"ic":"CR 12","q":12,"ict":"Breads","icd":"Cheeseroll Box of 12"}]
            'created_by_id' => 'required',
            'store_code' => 'required|string',
            'store_sub_unit_short_name' => 'nullable|string',
        ]);
        try {
            DB::beginTransaction();
            $directPurchaseNumber = $fields['direct_reference_number'];
            $supplierCode = $fields['supplier_code'];
            $supplierName = $fields['supplier_name'];
            $directPurchaseDate = $fields['direct_purchase_date'];
            $expectedDeliveryDate = $fields['expected_delivery_date'];
            $directPurchaseItems = $fields['direct_purchase_items'];
            $createdById = $fields['created_by_id'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'] ?? null;
            $type = $fields['type'];
            $directPurchaseModel = DirectPurchaseModel::create([
                'reference_number' => DirectPurchaseModel::onGenerateReferenceNumber(),
                'direct_reference_number' => $directPurchaseNumber,
                'type' => $type,
                'supplier_code' => $supplierCode,
                'supplier_name' => $supplierName,
                'direct_purchase_date' => $directPurchaseDate,
                'expected_delivery_date' => $expectedDeliveryDate,
                'created_by_id' => $createdById,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
            ]);

            $directPurchaseItemsArr = $this->onCreateDirectPurchaseItems($type, $directPurchaseModel->id, $directPurchaseItems, $createdById);

            $data = [
                'direct_purchase_details' => $directPurchaseModel,
                'direct_purchase_items' => $directPurchaseItemsArr
            ];
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'), $data);
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    private function onCreateDirectPurchaseItems($type, $directPurchaseId, $directPurchaseItems, $createdById)
    {
        try {
            $directPurchaseItems = json_decode($directPurchaseItems, true);

            $data = [];
            foreach ($directPurchaseItems as $items) {
                $itemCode = $items['ic'];
                $itemCategoryName = $items['ict'];
                $itemDescription = $items['icd'];
                $quantity = $items['q'];

                $directPurchaseItemModel = DirectPurchaseItemModel::create([
                    'direct_purchase_id' => $directPurchaseId,
                    'item_code' => $itemCode,
                    'item_description' => $itemDescription,
                    'item_category_name' => $itemCategoryName,
                    'total_received_quantity' => 0,
                    'requested_quantity' => $quantity,
                    'created_by_id' => $createdById
                ]);

                if ($type == 0) {
                    $directPurchaseHandledItemController = new DirectPurchaseHandledItemController();
                    $directPurchaseHandledItemRequest = new Request([
                        'direct_purchase_item_id' => $directPurchaseItemModel->id,
                        'type' => $type, // 0 = rejected, 1 = received
                        'received_date' => now()->format('Y-m-d'),
                        'quantity' => $quantity,
                        'created_by_id' => $createdById
                    ]);
                    $directPurchaseHandledItemController->onCreate($directPurchaseHandledItemRequest);
                }
                $data[] = [
                    'direct_purchase_id' => $directPurchaseId,
                    'item_code' => $itemCode,
                    'item_description' => $itemDescription,
                    'item_category_name' => $itemCategoryName,
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
    public function onGetCurrent($status, $direct_purchase_id = 0, $store_code, $sub_unit = null)
    {
        $whereFields = [
            'status' => $status,
            'store_code' => $store_code,
        ];

        if ($sub_unit != null) {
            $whereFields['store_sub_unit_short_name'] = $sub_unit;
        }

        $withFunction = null;
        if ($direct_purchase_id != 0) {
            $whereFields['id'] = $direct_purchase_id;
            $withFunction = 'directPurchaseItems.directPurchaseHandledItems';
        }

        return $this->readCurrentRecord(DirectPurchaseModel::class, null, $whereFields, $withFunction, ['id' => 'DESC'], 'Purchase Order');
    }

    public function onClose(Request $request, $direct_purchase_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required'
        ]);
        try {
            $directPurchaseModel = DirectPurchaseModel::find($direct_purchase_id);
            if ($directPurchaseModel) {
                DB::beginTransaction();
                $directPurchaseModel->status = 1;
                $directPurchaseModel->updated_by_id = $fields['created_by_id'];
                $directPurchaseModel->updated_at = now();
                $directPurchaseModel->save();

                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $directPurchaseModel);

            }
            return $this->dataResponse('error', 404, __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onUpdateDirectPurchaseDetails(Request $request, $direct_purchase_id)
    {
        $fields = $request->validate([
            'direct_reference_number' => 'required|unique:direct_purchases,direct_reference_number,' . $direct_purchase_id,
            'direct_purchase_items' => 'required|json', // [{"ic":"CR 12","q":12,"ict":"Breads","icd":"Cheeseroll Box of 12"}]
            'supplier_code' => 'required',
            'supplier_name' => 'required',
            'direct_purchase_date' => 'required|date',
            'expected_delivery_date' => 'required|date',
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $directPurchaseModel = DirectPurchaseModel::find($direct_purchase_id);
            if (!$directPurchaseModel) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }
            $directPurchaseModel->update([
                'direct_reference_number' => $fields['direct_reference_number'],
                'supplier_code' => $fields['supplier_code'],
                'supplier_name' => $fields['supplier_name'],
                'direct_purchase_date' => $fields['direct_purchase_date'],
                'expected_delivery_date' => $fields['expected_delivery_date'],
                'updated_by_id' => $fields['created_by_id'],
                'updated_at' => now()
            ]);

            $directPurchaseItems = json_decode($fields['direct_purchase_items'], true);
            foreach ($directPurchaseItems as $item) {
                $itemCode = $item['ic'];
                $itemCategoryName = $item['ict'];
                $itemDescription = $item['icd'];
                $quantity = $item['q'];

                $directPurchaseItemModel = DirectPurchaseItemModel::where([
                    'direct_purchase_id' => $direct_purchase_id,
                    'item_code' => $item['ic']
                ])->first();

                if ($directPurchaseItemModel && $directPurchaseItemModel->requested_quantity != $quantity) {
                    $directPurchaseItemModel->requested_quantity = $quantity;
                    $directPurchaseItemModel->item_description = $itemDescription;
                    $directPurchaseItemModel->item_category_name = $itemCategoryName;
                    $directPurchaseItemModel->updated_by_id = $fields['created_by_id'];
                    $directPurchaseItemModel->updated_at = now();
                    $directPurchaseItemModel->save();
                } else if (!$directPurchaseItemModel) {
                    DirectPurchaseItemModel::create([
                        'direct_purchase_id' => $direct_purchase_id,
                        'item_code' => $itemCode,
                        'item_description' => $itemDescription,
                        'item_category_name' => $itemCategoryName,
                        'requested_quantity' => $quantity,
                        'created_by_id' => $fields['created_by_id']
                    ]);
                }
            }
                 $data = [
                'direct_purchase_details' => $directPurchaseModel,
            ];
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'), $data);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onCancel(Request $request, $direct_purchase_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required'
        ]);
        try {
            $directPurchaseModel = DirectPurchaseModel::find($direct_purchase_id);
            if ($directPurchaseModel) {
                DB::beginTransaction();
                $directPurchaseModel->status = 2; // cancelled
                $directPurchaseModel->updated_by_id = $fields['created_by_id'];
                $directPurchaseModel->updated_at = now();
                $directPurchaseModel->save();

                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $directPurchaseModel);

            }
            return $this->dataResponse('error', 404, __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }
}
