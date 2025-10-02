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

  public function onGetCurrent(Request $request)
{
    try {
        $fields = $request->validate([
            'reference_number'   => 'required|string',
            'receive_type'       => 'required|string',
            'selected_item_codes'=> 'required|string',
        ]);

        $referenceNumber = $fields['reference_number'];
        $receiveType     = $fields['receive_type'];
        $selectedCodes   = json_decode($fields['selected_item_codes'], true);

        // Normalize item codes (take only the part before ":")
        $itemCodes = collect($selectedCodes)
            ->map(fn($code) => explode(':', $code)[0])
            ->unique()
            ->values();

        // Get latest cache entry
        $cacheModel = StoreReceivingInventoryItemCacheModel::where([
                'reference_number' => $referenceNumber,
                'receive_type'     => $receiveType,
            ])
            ->latest('id')
            ->first();

        if (!$cacheModel) {
            // Return success with empty data instead of error
            $emptyModel = new StoreReceivingInventoryItemCacheModel();
            $emptyModel->scanned_items = '[]';
            return $this->dataResponse('success', 200, __('msg.record_found'), $emptyModel);
        }

        // Decode scanned items safely
        $decodedItems = collect(json_decode($cacheModel->scanned_items, true) ?: []);

        // Filter items: either match item_code or new source
        $filteredItems = $decodedItems->filter(function ($item) use ($itemCodes) {
            return $itemCodes->contains($item['ic']) || strtolower($item['source'] ?? '') === 'new';
        })->values();

        // Replace scanned_items with filtered list (still JSON for consistency)
        $cacheModel->scanned_items = $filteredItems->toJson();

        return $this->dataResponse('success', 200, __('msg.record_found'), $cacheModel);
    } catch (Exception $exception) {
        return $this->dataResponse('error', 500, __('msg.record_not_found'), $exception->getMessage());
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
