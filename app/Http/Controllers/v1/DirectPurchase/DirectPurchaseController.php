<?php

namespace App\Http\Controllers\v1\DirectPurchase;

use App\Http\Controllers\Controller;
use App\Models\DirectPurchase\DirectPurchaseItemModel;
use App\Models\DirectPurchase\DirectPurchaseModel;
use App\Models\Stock\StockInventoryModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\Sap\SapPurchaseRequisitionTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use DB;

class DirectPurchaseController extends Controller
{
    use ResponseTrait, CrudOperationsTrait, SapPurchaseRequisitionTrait;

    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            // 'type' => 'required|in:0,1', // 0 = DR, 1 = PO
            // 'direct_reference_number' => 'required',
            'supplier_code' => 'nullable',
            'supplier_name' => 'required',
            'remarks' => 'nullable|string',
            'direct_purchase_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'direct_purchase_items' => 'nullable|json', //[{"item_code":"A5074","item_description":"STRAWBERRY JAM","item_category_code":"","uom":"PCE","requested_quantity":"5","total_received_quantity":"5","remarks":"test"}]
            'attachment' => 'nullable',
            'attachment.*' => 'file|mimes:jpg,jpeg,png|max:5120',
            'created_by_id' => 'required',
            'store_code' => 'required|string',
            'store_sub_unit_short_name' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            // $directReferenceNumber = $fields['direct_reference_number'];
            $supplierCode = $fields['supplier_code'];
            $supplierName = $fields['supplier_name'];
            $directPurchaseDate = $fields['direct_purchase_date'];
            $expectedDeliveryDate = $fields['expected_delivery_date'];
            $directPurchaseItems = $fields['direct_purchase_items'];
            $createdById = $fields['created_by_id'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'] ?? null;
            $remarks = $fields['remarks'] ?? null;
            // $type = $fields['type'];
            $directPurchaseAttachment = [];

            if ($request->hasFile('attachment')) {
                $files = $request->file('attachment');
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    $path = $file->store('attachments/direct_purchase', 'public');
                    $directPurchaseAttachment[] = [
                        'id' => (string) \Str::uuid(),
                        'url' => asset(\Storage::url($path)),
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'path' => $path,
                    ];
                }
            }
            $directPurchaseModel = DirectPurchaseModel::create([
                'reference_number' => DirectPurchaseModel::onGenerateReferenceNumber(),
                // 'direct_reference_number' => $directReferenceNumber,
                // 'type' => $type,
                'attachment' => $directPurchaseAttachment,
                'supplier_code' => $supplierCode,
                'supplier_name' => $supplierName,
                'direct_purchase_date' => $directPurchaseDate,
                'expected_delivery_date' => $expectedDeliveryDate,
                'created_by_id' => $createdById,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'status' => '1', // Posted / Complete
                'remarks' => $remarks,
            ]);

            $directPurchaseItemsArr = $this->onCreateDirectPurchaseItems($directPurchaseModel->id, $directPurchaseItems, $createdById);

            $data = [
                'direct_purchase_details' => $directPurchaseModel,
                'direct_purchase_items' => $directPurchaseItemsArr
            ];
            DB::commit();
            // $this->createSapPurchaseRequest($data);
            // return $this->dataResponse('success', 200, __('msg.create_success'), $data);
            return $this->dataResponse('success', 200, 'Direct Purchase Created Successfully.', $data);
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    private function onCreateDirectPurchaseItems($directPurchaseId, $directPurchaseItems, $createdById)
    {
        $directPurchaseItems = json_decode($directPurchaseItems, true) ?: [];

        try {
            $data = [];
            foreach ($directPurchaseItems as $items) {

                $directPurchaseItemModel = DirectPurchaseItemModel::create([
                    'direct_purchase_id' => $directPurchaseId,
                    'item_code' => $items['item_code'] ?? null,
                    'item_description' => $items['item_description'] ?? null,
                    'item_category_code' => 'A035',
                    'uom' => $items['uom'] ?? null,
                    'total_received_quantity' => $items['total_received_quantity'] ?? null,
                    'requested_quantity' => $items['requested_quantity'] ?? null,
                    'remarks' => $items['remarks'] ?? null,
                    'created_by_id' => $createdById,
                    'created_at' => now(),
                ]);

                $data[] = [
                    'direct_purchase_id' => $directPurchaseId,
                    'direct_purchase_item_id' => $directPurchaseItemModel->id,
                    'item_code' => $items['item_code'],
                    'item_description' => $items['item_description'],
                    'item_category_code' => 'A035',
                    'uom' => $items['uom'] ?? null,
                    'total_received_quantity' => $items['total_received_quantity'] ?? null,
                    'requested_quantity' => $items['requested_quantity'] ?? null,
                    // 'storage_location' => 'BKRM',
                    'remarks' => $items['remarks'] ?? null,
                    'created_by_id' => $createdById,
                    'created_at' => now(),
                ];
            }

            return $data;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
    #region Get Current Direct Purchase
    public function onGetCurrent($status, $store_code, $sub_unit = null)
    {
        try {
            $query = DirectPurchaseModel::query();
            $query->where('store_code', $store_code);
            if ($status == 1) {
                $query->where('status', 1);
            } else {
                $query->where('status', $status);
            }

            if ($sub_unit) {
                $query->where('store_sub_unit_short_name', $sub_unit);
            }

            $directPurchase = $query->orderBy('id', 'DESC')->get();
            if ($directPurchase->isEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_not_found'), []);
            }
            // Decode attachment field for each request
            $directPurchase->transform(function ($item) {
                if (!empty($item->attachment) && is_string($item->attachment)) {
                    $decoded = json_decode($item->attachment, true);
                    $item->attachment = $decoded ?: $item->attachment;
                }
                return $item;
            });
            return $this->dataResponse('success', 200, __('msg.record_found'), $directPurchase);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
    #region Get Direct Purchase By ID
    public function onGetById($direct_purchase_id)
    {
        try {
            $directPurchaseModel = DirectPurchaseModel::find($direct_purchase_id);
            if ($directPurchaseModel) {
                // Decode attachment field
                if (!empty($directPurchaseModel->attachment) && is_string($directPurchaseModel->attachment)) {
                    $decoded = json_decode($directPurchaseModel->attachment, true);
                    $directPurchaseModel->attachment = $decoded ?: $directPurchaseModel->attachment;
                }
                $data = [
                    'direct_purchase_header' => $directPurchaseModel,
                    'direct_purchase_items' => $directPurchaseModel->directPurchaseItems()->get(),
                ];
                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            } else {
                return $this->dataResponse('error', 404, __('msg.record_failed'));
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_failed'), $exception->getMessage());
        }
    }
    #endregion

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

    #region Update Direct Purchase
    public function onUpdateDirectPurchase(Request $request, $direct_purchase_id)
    {
        $fields = $request->validate([

            'supplier_code' => 'nullable',
            'supplier_name' => 'nullable',
            'direct_purchase_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            // existing attachments sent as JSON string
            'existing_attachments' => 'nullable|string',

            // newly uploaded files
            'attachment' => 'nullable|array',
            'attachment.*' => 'file|mimes:jpg,jpeg,png|max:5120',

            'direct_purchase_items' => 'nullable|json',
            'updated_by_id' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $directPurchase = DirectPurchaseModel::find($direct_purchase_id);
            if (!$directPurchase) {
                return $this->dataResponse('error', 404, 'Direct Purchase not found.');
            }

            // BASIC UPDATE

            $directPurchase->direct_purchase_date = $fields['direct_purchase_date'];
            $directPurchase->expected_delivery_date = $fields['expected_delivery_date'];
            $directPurchase->supplier_code = $fields['supplier_code'];
            $directPurchase->supplier_name = $fields['supplier_name'];
            $directPurchase->remarks = $fields['remarks'] ?? $directPurchase->remarks;
            $directPurchase->updated_by_id = $fields['updated_by_id'];
            $directPurchase->updated_at = now();

            // EXISTING ATTACHMENTS

            $existingAttachments = [];

            if (!empty($fields['existing_attachments'])) {
                $existingAttachments = json_decode($fields['existing_attachments'], true) ?? [];
            }

            // normalize old attachments from DB
            $oldAttachments = $purchaseRequest->attachment ?? [];

            //DELETE REMOVED FILES

            $existingPaths = collect($existingAttachments)->pluck('path')->filter()->toArray();

            foreach ($oldAttachments as $old) {
                if (
                    isset($old['path']) &&
                    !in_array($old['path'], $existingPaths)
                ) {
                    \Storage::disk('public')->delete($old['path']);
                }
            }

            // HANDLE NEW UPLOADS
            $newAttachments = [];

            if ($request->hasFile('attachment')) {
                foreach ($request->file('attachment') as $file) {

                    $path = $file->store('attachments/direct_purchase', 'public');

                    $newAttachments[] = [
                        'id' => (string) \Str::uuid(),
                        'url' => asset(\Storage::url($path)),
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'path' => $path,
                    ];
                }
            }

            // FINAL MERGE
            $directPurchase->attachment = array_values(
                array_merge($existingAttachments, $newAttachments)
            );

            $directPurchase->save();
            // UPDATE ITEMS
            if (!empty($fields['direct_purchase_items'])) {
                DirectPurchaseItemModel::where('direct_purchase_id', $direct_purchase_id)->delete();
                $this->onCreateDirectPurchaseItems(
                    $direct_purchase_id,
                    $fields['updated_by_id'],
                    $fields['direct_purchase_items']
                );
            }
            //TODO SAP Update Direct Purchase Details
            DB::commit();
            return $this->dataResponse('success', 200, 'Direct Purchase Updated Successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            return $this->dataResponse('error', 500, 'Update failed', $e->getMessage());
        }
    }

    #region Cancel Direct Purchase
    public function onCancel(Request $request, $purchase_request_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $purchaseRequestModel = DirectPurchaseModel::whereIn('status', [0, 1])->find($purchase_request_id);
            if (!$purchaseRequestModel) {
                return $this->dataResponse('success', 200, __('msg.record_not_found'));
            }
            $purchaseRequestModel->status = 2; // Set status to Cancelled
            $purchaseRequestModel->updated_by_id = $createdById;
            $purchaseRequestModel->save();
            DB::commit();
            //TODO SAP Update PR Status to Cancelled
            return $this->dataResponse('success', 200, 'Cancelled Successfully');
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Cancel Failed', $exception->getMessage());
        }
    }
    #endregion


}
