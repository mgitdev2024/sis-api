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
            'reference_number' => 'required', // 22145
            'scanned_items' => 'required', // {"bid":1,"item_code":"CR 12","q":1},{"bid":1,"item_code":"CR 12","q":1}
            'created_by_id' => 'required', // 0000
            'receive_type' => 'required', // 0 = scan 1 = manual
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

    public function onGetCurrent($reference_number, $receive_type, $selected_item_codes)
    {
        try {
            $storeReceivingInventoryItemCacheModel = StoreReceivingInventoryItemCacheModel::where([
                'reference_number' => $reference_number,
                'receive_type' => $receive_type
            ])->first();

            if ($storeReceivingInventoryItemCacheModel) {
                $decodedItems = json_decode($storeReceivingInventoryItemCacheModel->scanned_items, true);

                // Filter only items whose 'ic' exists in $selected_item_codes
                $filteredItems = array_values(array_filter($decodedItems, function ($item) use ($selected_item_codes) {
                    return in_array($item['ic'], $selected_item_codes);
                }));

                $storeReceivingInventoryItemCacheModel->scanned_items = $filteredItems;

                return $this->dataResponse('success', 200, __('msg.record_found'), $storeReceivingInventoryItemCacheModel);
            }

            return $this->dataResponse('error', 404, __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }


    public function onDelete($reference_number)
    {
        try {
            $storeReceivingInventoryItemCacheModel = StoreReceivingInventoryItemCacheModel::where('reference_number', $reference_number)->delete();
            if ($storeReceivingInventoryItemCacheModel) {
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }
            return $this->dataResponse('error', 404, __('msg.record_not_found'), __('msg.delete_failed'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
