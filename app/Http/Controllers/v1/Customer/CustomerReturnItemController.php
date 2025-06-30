<?php

namespace App\Http\Controllers\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\CustomerReturnFormModel;
use App\Models\Customer\CustomerReturnItemModel;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Exception;
class CustomerReturnItemController extends Controller
{
    use ResponseTrait;
    public function onGetById($customer_return_form_id)
    {
        try {
            $data = [];
            $customerReturnFormModel = CustomerReturnFormModel::find($customer_return_form_id);
            $customerReturnItemModel = CustomerReturnItemModel::where('customer_return_form_id', $customer_return_form_id)->get();
            if (count($customerReturnItemModel) <= 0) {
                return $this->dataResponse('error', 200, __('msg.record_not_found'));
            }
            $data['customer_return_form_details'] = $customerReturnFormModel;
            $data['customer_return_items'] = $customerReturnItemModel;
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
