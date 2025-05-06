<?php

namespace App\Http\Controllers\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\CustomerReturnFormModel;
use App\Models\Customer\CustomerReturnItemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
use DB;
class CustomerReturnFormController extends Controller
{
    use ResponseTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'store_code' => 'required',
            'store_sub_unit_short_name' => 'required',
            'attachment' => 'required',
            'official_receipt_number' => 'required',
            'remarks' => 'nullable',
            'returned_items' => 'required', // [{"ic":"CR 12","idc":"Cheeseroll Box of 12","icn":"BREADS","q":1}]
        ]);

        try {
            DB::beginTransaction();
            $attachmentPath = $request->file('attachment')->store('public/attachments/customer_returns');
            $filepath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);

            $referenceNumber = CustomerReturnFormModel::onGenerateReferenceNumber();
            $customerReturnFormModel = CustomerReturnFormModel::create([
                'reference_number' => $referenceNumber,
                'store_code' => $fields['store_code'],
                'store_sub_unit_short_name' => $fields['store_sub_unit_short_name'],
                'official_receipt_number' => $fields['official_receipt_number'],
                'attachment' => $filepath,
                'remarks' => $fields['remarks'] ?? null,
                'created_by_id' => $fields['created_by_id'],
                'updated_by_id' => $fields['created_by_id'],
            ]);

            $customerReturnFormId = $customerReturnFormModel->id;
            $this->onCreateCustomerReturnItems($customerReturnFormId, $fields['returned_items'], $fields['created_by_id']);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 404, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onCreateCustomerReturnItems($customerReturnFormId, $returnedItems, $createdById)
    {
        try {
            $returnedItemsArr = json_decode($returnedItems, true);

            foreach ($returnedItemsArr as $item) {
                CustomerReturnItemModel::create([
                    'customer_return_form_id' => $customerReturnFormId,
                    'item_code' => $item['ic'],
                    'item_description' => $item['idc'],
                    'item_category_name' => $item['icn'],
                    'quantity' => $item['q'],
                    'created_by_id' => $createdById,
                ]);
            }
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception('Failed to create customer return items');
        }
    }

    public function onGetCurrent($store_code, $store_sub_unit_short_name = null)
    {
        try {
            $customerReturnForms = CustomerReturnFormModel::where([
                'store_code' => $store_code,
                'store_sub_unit_short_name' => $store_sub_unit_short_name,
            ])->get();

            return $this->dataResponse('success', 200, __('msg.get_success'), $customerReturnForms);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.get_failed'), $exception->getMessage());
        }

    }
}
