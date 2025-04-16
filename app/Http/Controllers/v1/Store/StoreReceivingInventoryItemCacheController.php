<?php

namespace App\Http\Controllers\v1\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\StoreReceivingInventoryItemCacheModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Http\Request;
use Exception;
class StoreReceivingInventoryItemCacheController extends Controller
{
    use ResponseTrait, CrudOperationsTrait;
    public function onCreate(Request $request, $store_code)
    {
        $fields = $request->validate([
            'order_session_id' => 'required', // 22145
            'scanned_items' => 'required', // {"bid":1,"item_code":"CR 12","q":1},{"bid":1,"item_code":"CR 12","q":1}
            'created_by_id' => 'required' // 0000
        ]);
        try {
            $fields['store_code'] = $store_code;
            DB::beginTransaction();
            $storeReceivingInventoryItemCacheModel = new StoreReceivingInventoryItemCacheModel();
            $storeReceivingInventoryItemCacheModel->create($fields);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onGetCurrent($order_session_id)
    {
        try {
            $whereFields = [
                'order_session_id' => $order_session_id,
            ];
            return $this->readCurrentRecord(StoreReceivingInventoryItemCacheModel::class, null, $whereFields, null, null, 'Store Receiving Inventory Item Cache', false, null, 1);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }

    public function onDelete($order_session_id)
    {
        try {
            $storeReceivingInventoryItemCacheModel = StoreReceivingInventoryItemCacheModel::where('order_session_id', $order_session_id)->delete();
            if ($storeReceivingInventoryItemCacheModel) {
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }
            return $this->dataResponse('error', 404, __('msg.record_not_found'), __('msg.delete_failed'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
