<?php

namespace App\Http\Controllers\v1\WMS\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\WMS\Dispatch\StockDispatchModel;
use App\Models\WMS\InventoryKeeping\GeneratePicklist\GeneratePickListItemModel;
use Illuminate\Http\Request;
use DB;
use Exception;
use App\Traits\WMS\QueueSubLocationTrait;
use App\Traits\WMS\WarehouseLogTrait;
class StockDispatchController extends Controller
{
    use QueueSubLocationTrait, WarehouseLogTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'generate_picklist_id' => 'required|exists:wms_generate_picklists,id',
            'created_by_id' => 'required',
            'store_details' => 'nullable|json',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $storeDetails = json_decode($fields['store_details'], true);

            $stockDispatch = new StockDispatchModel();
            $stockDispatch->reference_number = StockDispatchModel::onGenerateStockDispatchReferenceNumber();
            $stockDispatch->generate_picklist_id = $fields['generate_picklist_id'];
            $stockDispatch->created_by_id = $createdById;
            $stockDispatch->save();

            $storeDetails = json_decode($fields['store_details'] ?? '[]', true);
            $storeName = $storeDetails['store_name'] ?? null;
            $storeId = $storeDetails['store_id'] ?? null;
            $generatePickListItems = GeneratePickListItemModel::where('generate_picklist_id', $fields['generate_picklist_id']);
            if ($storeDetails != null) {
                $generatePickListItems = $generatePickListItems->where('store_id', $storeId);
            }
            $generatePickListItems = $generatePickListItems->first();

            dd($generatePickListItems->picklist_items);
            // NOTE: Please continue working on this function.
            // REQUIREMENTS: This function should be able to handle both picklist items and picked items from the get go. Might check with bakery personnel about this.
            // TODO
            // !this is an danger must create
            DB::commit();
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Stock Dispatch ' . __('msg.update_failed'), $exception->getMessage());
        }
    }
}
