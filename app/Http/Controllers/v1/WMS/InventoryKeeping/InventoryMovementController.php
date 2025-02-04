<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionItemModel;
use App\Traits\WMS\InventoryMovementTrait;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use Exception;
use DB;
class InventoryMovementController extends Controller
{
    use WmsCrudOperationsTrait, InventoryMovementTrait;

    public function onGetInventoryMovementStats($date)
    {
        try {
            // To Receive
            $formattedDate = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$formattedDate->format('Y-m-d') === $date) {
                return $this->dataResponse('error', 200, 'Invalid date');
            }
            $toReceiveQuantity = $this->onGetReceiveItems($date, 'to receive');
            // For Put Away

            // Stock Transfer

            // For Distribution
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Inventory Movement ' . __('msg.record_not_found'));
        }
    }

    public function onRevertToStoredItem(Request $request)
    {
        $fields = $request->validate([
            'production_batch_ids' => 'required',
            'key_to_unset' => 'required' // Stored Sub Location
        ]);
        try {
            DB::beginTransaction();
            $productionBatchIdArray = json_decode($fields['production_batch_ids'], true);
            $keyToUnset = $fields['key_to_unset'];

            foreach ($productionBatchIdArray as $batchId) {
                $productionItemModel = ProductionItemModel::where('production_batch_id', $batchId)->first();
                $producedItems = json_decode($productionItemModel->produced_items, true);
                foreach ($producedItems as &$items) {
                    unset($items[$keyToUnset]);
                }
                $productionItemModel->produced_items = json_encode($producedItems);
                $productionItemModel->save();
            }
            DB::commit();
            return $this->dataResponse('success', 200, 'Inventory Movement ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Inventory Movement ' . __('msg.update_failed'));
        }
    }
}
