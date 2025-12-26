<?php

namespace App\Http\Controllers\v1\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Sap\PurchaseRequest\PurchaseRequestItemModel;
use App\Models\Sap\PurchaseRequest\PurchaseRequestModel;
use App\Models\Sap\PurchaseRequest\PurchaseRequestTemplateModel;
use App\Traits\ResponseTrait;
use App\Traits\Sap\SapPurchaseRequisitionTrait;
use DB, Http, Exception, Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class PurchaseRequestController extends Controller
{
    use ResponseTrait, SapPurchaseRequisitionTrait;
    public function onCreate(Request $request)
    {

        $fields = $request->validate([
            'type' => 'required|in:0,1', //* 0 = Regular PR, 1 = Staggered PR
            'remarks' => 'nullable|string',
            'store_code' => 'required|string',
            'store_sub_unit_short_name' => 'required|string',
            'storage_location' => 'nullable|string',
            'created_by_id' => 'required',
            'attachment' => 'nullable',
            'attachment.*' => 'file|mimes:jpg,jpeg,png|max:5120',
            'purchase_request_items' => 'nullable|json',
            'selection_template' => 'nullable',
            'delivery_date' => 'required|date',
        ]);

        try {

            DB::beginTransaction();
            $type = $fields['type'];
            $remarks = $fields['remarks'] ?? null;
            $expectedDeliveryDate = $fields['delivery_date'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];
            $storageLocation = $fields['storage_location'];
            $purchaseRequestItems = $fields['purchase_request_items'];
            $createdById = $fields['created_by_id'];
            $purchaseRequestAttachment = [];
            $selectionTemplate = $fields['selection_template'] ?? null;

            if ($request->hasFile('attachment')) {
                $files = $request->file('attachment');
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    $path = $file->store('attachments/purchase_request', 'public');
                    $purchaseRequestAttachment[] = [
                        'id' => (string) \Str::uuid(),
                        'url' => asset(Storage::url($path)),
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'path' => $path,
                    ];
                }
            }

            $purchaseRequestModel = PurchaseRequestModel::create([
                'reference_number' => PurchaseRequestModel::onGenerateReferenceNumber(),
                'type' => $type,
                'remarks' => $remarks,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'store_company_code' => 'BMII',
                'storage_location' => $storageLocation,
                'attachment' => $purchaseRequestAttachment,
                'delivery_date' => $expectedDeliveryDate,
                'status' => '1', //* Default pending upon PR (Viewing Purposes) // * 0 = Closed PR, 2 = For Receive, 3 = For PO, 1 = Pending
                'created_by_id' => $createdById,
                'created_at' => now(),
            ]);

            $purchaseRequestItemsArr = $this->onCreatePurchaseRequestItems($purchaseRequestModel->id, $createdById, $purchaseRequestItems);
            if ($selectionTemplate != null) {
                $this->onSavePurchaseRequestTemplate($selectionTemplate, $storeCode, $storeSubUnitShortName, $createdById);
            }
            $data = [
                'purchase_request_header' => $purchaseRequestModel,
                'purchase_request_items' => $purchaseRequestItemsArr
            ];

            DB::commit();
            // $this->createSapPurchaseRequest($data);

            return $this->dataResponse('success', 200, 'Purchase Request Created Successfully.');

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onCreatePurchaseRequestItems($purchaseRequestModelId, $createdById, $purchaseRequestItems)
    {
        $purchaseRequestItems = json_decode($purchaseRequestItems, true);

        try {
            $data = [];
            foreach ($purchaseRequestItems as $items) {

                $purchaseRequestItemModel = PurchaseRequestItemModel::create([
                    'purchase_request_id' => $purchaseRequestModelId,
                    'item_code' => $items['item_code'] ?? null,
                    'item_name' => $items['item_name'] ?? null,
                    'item_category_code' => 'A035',
                    'uom' => $items['uom'] ?? null,
                    'purchasing_organization' => 'MGPO',
                    'purchasing_group' => '001',
                    'requested_quantity' => $items['requested_quantity'] ?? null,
                    'price' => '1',
                    'currency' => 'PHP',
                    'delivery_date' => null,
                    'remarks' => $items['remarks'] ?? null,
                    'created_by_id' => $createdById,
                    'created_at' => now(),
                ]);

                $data[] = [
                    'purchase_request_id' => $purchaseRequestModelId,
                    'purchase_request_item_id' => $purchaseRequestItemModel->id,
                    'item_code' => $items['item_code'],
                    'item_name' => $items['item_name'],
                    'item_category_code' => 'A035',
                    'uom' => $items['uom'] ?? null,
                    'purchasing_organization' => 'MGPO',
                    'purchasing_group' => '001',
                    'requested_quantity' => $items['requested_quantity'] ?? null,
                    'price' => '1',
                    'currency' => 'PHP',
                    'delivery_date' => null,
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

    #region onGet
    public function onGetCurrent($status, $store_code, $sub_unit = null)
    {
        try {
            $query = PurchaseRequestModel::query();
            if ($status == 1) {
                $query->where('store_code', $store_code);
            } else {
                $query->where('store_code', $store_code)
                    ->whereIn('status', [0, 2, 3]);
            }

            if ($sub_unit) {
                $query->where('store_sub_unit_short_name', $sub_unit);
            }

            $purchaseRequests = $query->orderBy('id', 'DESC')->get();
            if ($purchaseRequests->isEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_not_found'), []);
            }
            // Decode attachment field for each request
            $purchaseRequests->transform(function ($item) {
                if (!empty($item->attachment) && is_string($item->attachment)) {
                    $decoded = json_decode($item->attachment, true);
                    $item->attachment = $decoded ?: $item->attachment;
                }
                return $item;
            });
            return $this->dataResponse('success', 200, __('msg.record_found'), $purchaseRequests);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onGetById($purchase_request_id)
    {
        try {
            $purchaseRequestModel = PurchaseRequestModel::find($purchase_request_id);
            if ($purchaseRequestModel) {
                // Decode attachment field
                if (!empty($purchaseRequestModel->attachment) && is_string($purchaseRequestModel->attachment)) {
                    $decoded = json_decode($purchaseRequestModel->attachment, true);
                    $purchaseRequestModel->attachment = $decoded ?: $purchaseRequestModel->attachment;
                }
                $data = [
                    'purchase_request_header' => $purchaseRequestModel,
                    'purchase_request_items' => $purchaseRequestModel->purchaseRequestItems()->get(),
                ];
                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            } else {
                return $this->dataResponse('error', 404, __('msg.record_failed'));
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_failed'), $exception->getMessage());
        }
    }
    #region onUpdate
    public function onUpdate(Request $request, $purchase_request_id)
    {
        $fields = $request->validate([
            'delivery_date' => 'required|date',
            'remarks' => 'nullable|string',

            // existing attachments sent as JSON string
            'existing_attachments' => 'nullable|string',

            // newly uploaded files
            'attachment' => 'nullable|array',
            'attachment.*' => 'file|mimes:jpg,jpeg,png|max:5120',

            'purchase_request_items' => 'nullable|json',
            'updated_by_id' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $purchaseRequest = PurchaseRequestModel::find($purchase_request_id);
            if (!$purchaseRequest) {
                return $this->dataResponse('error', 404, 'Purchase Request not found.');
            }

            // BASIC UPDATE

            $purchaseRequest->delivery_date = $fields['delivery_date'];
            $purchaseRequest->remarks = $fields['remarks'] ?? $purchaseRequest->remarks;
            $purchaseRequest->updated_by_id = $fields['updated_by_id'];
            $purchaseRequest->updated_at = now();

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

                    $path = $file->store('attachments/purchase_request', 'public');

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
            $purchaseRequest->attachment = array_values(
                array_merge($existingAttachments, $newAttachments)
            );

            $purchaseRequest->save();

            // UPDATE ITEMS
            if (!empty($fields['purchase_request_items'])) {
                PurchaseRequestItemModel::where('purchase_request_id', $purchase_request_id)->delete();
                $this->onCreatePurchaseRequestItems(
                    $purchase_request_id,
                    $fields['updated_by_id'],
                    $fields['purchase_request_items']
                );
            }

            DB::commit();
            return $this->dataResponse('success', 200, 'Purchase Request Updated Successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            return $this->dataResponse('error', 500, 'Update failed', $e->getMessage());
        }
    }
    public function onSavePurchaseRequestTemplate($selectionTemplate, $storeCode, $storeSubUnitShortName, $createdById)
    {
        try {
            $existingTemplate = PurchaseRequestTemplateModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName
            ])->first();

            if ($existingTemplate) {
                $existingTemplate->selection_template = $selectionTemplate;
                $existingTemplate->updated_by_id = $createdById;
                $existingTemplate->save();
            } else {
                PurchaseRequestTemplateModel::create([
                    'store_code' => $storeCode,
                    'store_sub_unit_short_name' => $storeSubUnitShortName,
                    'selection_template' => $selectionTemplate,
                    'created_by_id' => $createdById,
                    'updated_by_id' => $createdById,
                    'status' => 1, // Active
                ]);
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onCancel(Request $request, $purchase_request_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $purchaseRequestModel = PurchaseRequestModel::whereIn('status', [2, 3])->find($purchase_request_id);
            if (!$purchaseRequestModel) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }
            $purchaseRequestModel->status = 3; // Set status to Cancelled
            $purchaseRequestModel->updated_by_id = $createdById;
            $purchaseRequestModel->save();
            DB::commit();
            return $this->dataResponse('success', 200, 'Cancelled Successfully');
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Cancel Failed', $exception->getMessage());
        }
    }
}