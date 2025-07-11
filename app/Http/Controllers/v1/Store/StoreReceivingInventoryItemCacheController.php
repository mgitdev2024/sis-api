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
            $selected_item_codes = str_replace(":", "", $selected_item_codes);
            $storeReceivingInventoryItemCacheModel = StoreReceivingInventoryItemCacheModel::where([
                'reference_number' => $reference_number,
                'receive_type' => $receive_type
            ])->orderBy('id', 'DESC')->first();

            if ($storeReceivingInventoryItemCacheModel) {
                $decodedItems = json_decode($storeReceivingInventoryItemCacheModel->scanned_items, true);
                $itemCodes = json_decode($selected_item_codes, true);
 
                $filteredItems = array_values(array_filter($decodedItems, function ($item) use ($itemCodes) { // Return items that are selected from the store receiving and also return the new or wrong dropped items
                    return in_array($item['ic'], $itemCodes) || strtolower($item['source']) === 'new';
                })); 

                $storeReceivingInventoryItemCacheModel->scanned_items = $filteredItems;

                return $this->dataResponse('success', 200, __('msg.record_found'), $storeReceivingInventoryItemCacheModel);
            }

            return $this->dataResponse('error', 404, __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 404, __('msg.record_not_found'), $exception->getMessage());
        }
    }
    public function onGetCurrentScanning($reference_number, $receive_type)
    {
        try {
            $whereFields = [
                'reference_number' => $reference_number,
                'receive_type' => $receive_type,
            ];
            return $this->readCurrentRecord(StoreReceivingInventoryItemCacheModel::class, null, $whereFields, null, ['id' => 'DESC'], 'Store Receiving Inventory Item Cache', false, null, 1);
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
