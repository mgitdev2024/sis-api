<?php

namespace App\Http\Controllers\v1\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\Sap\PurchaseRequest\PurchaseRequestItemModel;
use App\Models\Sap\PurchaseRequest\PurchaseRequestModel;
use App\Traits\ResponseTrait;
use App\Traits\Sap\SapPurchaseRequisitionTrait;
use DB, Http, Exception, Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'attachment' => 'nullable|array',
            'attachment.*' => 'file|mimes:jpg,jpeg,png|max:5120',
            'purchase_request_items' => 'nullable|json',
            'delivery_date' => 'required|date',
        ]);
        // dd($fields);
        try {

            DB::beginTransaction();
            $createdBy = Auth::user()->id;
            $type = $fields['type'];
            $remarks = $fields['remarks'] ?? null;
            $expectedDeliveryDate = $fields['delivery_date'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];
            $storageLocation = $fields['storage_location'];
            $purchaseRequestItems = $fields['purchase_request_items'];
            // $purchaseRequestAttachment = null;
            // dd($storageLocation);
            // if ($request->hasFile('attachment')) {
            //     $attachmentPath = $request->file('attachment')->store('public/attachments/purchase_request');
            //     $filepath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);
            //     $purchaseRequestAttachment = $filepath;
            // }
            $purchaseRequestAttachment = [];

            if ($request->hasFile('attachment')) {
                foreach ($request->file('attachment') as $file) {

                    $path = $file->store(
                        'public/attachments/purchase_request'
                    );

                    $purchaseRequestAttachment[] =
                        env('APP_URL') . '/storage/' . substr($path, 7);
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
                'attachment' => !empty($purchaseRequestAttachment)
                    ? json_encode($purchaseRequestAttachment)
                    : null,
                'delivery_date' => $expectedDeliveryDate,
                'status' => '1', //* Default pending upon PR (Viewing Purposes) // * 0 = Closed PR, 2 = For Receive, 3 = For PO, 1 = Pending
                'created_by_id' => $createdBy,
                'created_at' => now(),
            ]);

            $purchaseRequestItemsArr = $this->onCreatePurchaseRequestItems($purchaseRequestModel->id, $purchaseRequestModel, $purchaseRequestItems);

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

    public function onCreatePurchaseRequestItems($purchaseRequestModelId, $purchaseRequestModel, $purchaseRequestItems)
    {
        $purchaseRequestItems = json_decode($purchaseRequestItems, true);
        $createdBy = Auth::user()->id;
        try {
            $data = [];
            foreach ($purchaseRequestItems as $items) {

                $purchaseRequestItemModel = PurchaseRequestItemModel::create([
                    'purchase_request_id' => $purchaseRequestModelId,
                    'item_code' => $items['item_code'],
                    'item_name' => $items['item_name'],
                    'item_category_code' => 'A035',
                    'purchasing_organization' => 'MGPO',
                    'purchasing_group' => '001',
                    'requested_quantity' => $items['requested_quantity'],
                    'price' => $items['price'],
                    'currency' => 'PHP',
                    'delivery_date' => $items['delivery_date'],
                    'remarks' => $items['remarks'],
                    'created_by_id' => $createdBy,
                    'created_at' => now(),
                ]);

                $data[] = [
                    'purchase_request_id' => $purchaseRequestModelId,
                    'purchase_request_item_id' => $purchaseRequestItemModel->id,
                    'item_code' => $items['item_code'],
                    'item_name' => $items['item_name'],
                    'item_category_code' => 'A035',
                    'purchasing_organization' => 'MGPO',
                    'purchasing_group' => '001',
                    'requested_quantity' => $items['requested_quantity'],
                    'price' => $items['price'],
                    'currency' => 'PHP',
                    'delivery_date' => $items['delivery_date'],
                    // 'storage_location' => 'BKRM',
                    'remarks' => $items['remarks'],
                    'created_by_id' => $createdBy,
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
            if ($status == 4) {
                $query->where('store_code', $store_code);
            } else {
                $query->where('store_code', $store_code)
                    ->whereIn('status', [0, 2, 3]);
            }

            if ($sub_unit) {
                $query->where('store_sub_unit_short_name', $sub_unit);
            }

            $purchaseRequests = $query->orderBy('id', 'DESC')
                ->get();
            if ($purchaseRequests->isEmpty()) {
                return $this->dataResponse('success', 200, __('msg.record_not_found'), []);
            }
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


    public function onUpdate(Request $request, $purchase_request_id)
    {
        $fields = $request->validate([
            'delivery_date' => 'required|date',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png',
            'purchase_request_items' => 'nullable|json',
            'updated_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $purchaseRequest = PurchaseRequestModel::find($purchase_request_id);
            if (!$purchaseRequest) {
                return $this->dataResponse('error', 404, __('msg.record_failed'), 'Purchase Request not found.');
            }

            $purchaseRequest->delivery_date = $fields['delivery_date'];
            $purchaseRequest->remarks = $fields['remarks'] ?? $purchaseRequest->remarks;
            $purchaseRequest->updated_by_id = $fields['updated_by_id'];
            $purchaseRequest->updated_at = now();

            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('public/attachments/purchase_request');
                $filepath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);
                $purchaseRequest->attachment = $filepath;
            }

            $purchaseRequest->save();

            // Update items if provided
            if (!empty($fields['purchase_request_items'])) {
                // Remove old items
                PurchaseRequestItemModel::where('purchase_request_id', $purchase_request_id)->delete();
                // Create new items
                $this->onCreatePurchaseRequestItems($purchase_request_id, $fields['purchase_request_items']);
            }

            DB::commit();
            return $this->dataResponse('success', 200, 'Purchase Request Updated Successfully.');
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.update_failed'), $exception->getMessage());
        }
    }

}