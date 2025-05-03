<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryItemCountModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class StockInventoryItemCountController extends Controller
{
    use ResponseTrait, CrudOperationsTrait;
    public function onGetById($store_inventory_count_id)
    {
        $whereFields = [
            'stock_inventory_count_id' => $store_inventory_count_id,
        ];
        $orderFields = [
            'item_category_name' => 'ASC',
        ];
        return $this->readCurrentRecord(StockInventoryItemCountModel::class, null, $whereFields, null, $orderFields, 'Store Inventory Item Count');
    }

    public function onUpdate(Request $request, $store_inventory_count_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'stock_inventory_count_data' => 'required' // [{"ic":"CR 12","cq":12},{"ic":"TAS WH","cq":1}]
        ]);

        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $stockInventoryCountData = json_decode($fields['stock_inventory_count_data'], true);

            foreach ($stockInventoryCountData as $item) {
                $itemCode = $item['ic']; // Item Code
                $countedQuantity = $item['cq']; // Counted Quantity

                $stockInventoryItemCount = StockInventoryItemCountModel::where([
                    'stock_inventory_count_id' => $store_inventory_count_id,
                    'item_code' => $itemCode,
                ])->first();

                if ($stockInventoryItemCount) {
                    $discrepancyQuantity = $stockInventoryItemCount->system_quantity - $countedQuantity;
                    $stockInventoryItemCount->update([
                        'counted_quantity' => $countedQuantity,
                        'discrepancy_quantity' => $discrepancyQuantity,
                        'updated_by_id' => $createdById,
                    ]);
                }
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.update_failed'), $exception->getMessage());
        }
    }
}
