<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryCountModel;
use App\Models\Stock\StockInventoryItemCountModel;
use App\Models\Stock\StockInventoryModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use DB;
class StockInventoryCountController extends Controller
{
    use ResponseTrait, CrudOperationsTrait;
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'store_code' => 'required',
            'store_sub_unit_short_name' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];
            $referenceNumber = StockInventoryCountModel::onGenerateReferenceNumber();

            $stockInventoryCount = StockInventoryCountModel::create([
                'reference_number' => $referenceNumber,
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'created_by_id' => $createdById,
                'updated_by_id' => $createdById,
                'status' => 0,
            ]);
            $stockInventoryCount->save();

            $this->onCreateStockInventoryItemsCount($stockInventoryCount->id, $storeCode, $storeSubUnitShortName, $createdById);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'), $exception->getMessage());
        }
    }

    public function onCreateStockInventoryItemsCount($stockInventoryCountId, $storeCode, $storeSubUnitShortName, $createdById)
    {
        try {
            $stockInventoryModel = StockInventoryModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
            ])->orderBy('item_code', 'ASC')->get();

            $stockInventoryItemsCount = [];
            foreach ($stockInventoryModel as $item) {
                $stockInventoryItemsCount[] = [
                    'stock_inventory_count_id' => $stockInventoryCountId,
                    'item_code' => $item->item_code,
                    'item_description' => $item->item_description,
                    'item_category_name' => $item->item_category_name,
                    'system_quantity' => $item->stock_count,
                    'counted_quantity' => 0,
                    'discrepancy_quantity' => 0,
                    'created_at' => Carbon::now(),
                    'created_by_id' => $createdById,
                    'updated_by_id' => $createdById,
                    'status' => 1, // For Receive
                ];
            }
            StockInventoryItemCountModel::insert($stockInventoryItemsCount);

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onGet($status, $store_code, $sub_unit = null)
    {
        try {
            $whereFields = [
                'status' => $status,
                'store_code' => $store_code,
            ];
            if ($sub_unit) {
                $whereFields['store_sub_unit_short_name'] = $sub_unit;
            }

            $orderFields = [
                'id' => 'DESC',
            ];
            $array = $this->readCurrentRecord(StockInventoryCountModel::class, null, $whereFields, null, $orderFields, 'Stock Inventory Count', false, null, null);

            if ($array->getOriginalContent()['success']['data']->isEmpty()) {
                return $this->dataResponse('error', 200, __('msg.record_not_found'));
            }
            $formatted = collect($array->getOriginalContent()['success']['data']->toArray())->map(function ($item) {
                if (isset($item['created_at'])) {
                    $item['created_at'] = Carbon::parse($item['created_at'])->format('Y-m-d H:i:s');
                    $item['updated_at'] = Carbon::parse($item['created_at'])->format('Y-m-d H:i:s');
                }
                return $item;
            })->toArray();
            return $this->dataResponse('success', 200, __('msg.record_found'), $formatted);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'), $exception->getMessage());
        }
    }
}
