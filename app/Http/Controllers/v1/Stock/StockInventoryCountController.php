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
            'type' => 'required|in:1,2,3', // 1 = Hourly, 2 = EOD, 3 = Month-End
            'store_code' => 'required',
            'store_sub_unit_short_name' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $storeCode = $fields['store_code'];
            $storeSubUnitShortName = $fields['store_sub_unit_short_name'];

            $hasPending = StockInventoryCountModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'status' => 0
            ])->exists();

            if ($hasPending) {
                return $this->dataResponse('success', 400, 'Still has pending stock count');
            }
            $referenceNumber = StockInventoryCountModel::onGenerateReferenceNumber();
            $type = $fields['type'];

            $stockCountDate = now();

            $stockInventoryCount = StockInventoryCountModel::create([
                'reference_number' => $referenceNumber,
                'type' => $type, // 1 = Hourly, 2 = EOD, 3 = Month-End
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'created_by_id' => $createdById,
                'updated_by_id' => $createdById,
                'status' => 0,
                'created_at' => $stockCountDate
            ]);
            $stockInventoryCount->save();
            $response = \Http::withHeaders([
                'x-api-key' => env('MGIOS_API_KEY'),
            ])->get(env('MGIOS_URL') . "/public/stock-count-lead-time/current/get");

            if ($response->successful()) {
                $leadTime = $response->json()['success']['data'] ?? [];
                $leadTimeFrom = $leadTime['lead_time_from'] ?? null;
                $leadTimeTo = $leadTime['lead_time_to'] ?? null;
                $currentTime = now()->format('H:i:s');

                if (
                    Carbon::createFromTimeString($currentTime)
                        ->between(
                            Carbon::createFromTimeString($leadTimeFrom),
                            Carbon::createFromTimeString($leadTimeTo)
                        )
                ) {
                    $stockCountDate = now()->subDay(); // yesterday
                }
            }

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
            $existingItemCodes = [];
            $stockInventoryModel = StockInventoryModel::where([
                'store_code' => $storeCode,
                'store_sub_unit_short_name' => $storeSubUnitShortName,
            ])->orderBy('item_code', 'DESC')->get();

            $stockInventoryItemsCount = [];
            foreach ($stockInventoryModel as $item) {
                $existingItemCodes[] = $item->item_code;
                $stockInventoryItemsCount[] = [
                    'stock_inventory_count_id' => $stockInventoryCountId,
                    'item_code' => $item->item_code,
                    'item_description' => $item->item_description,
                    'item_category_name' => $item->item_category_name,
                    'system_quantity' => $item->stock_count,
                    'counted_quantity' => 0,
                    'discrepancy_quantity' => 0,
                    'created_at' => now(),
                    'created_by_id' => $createdById,
                    'updated_by_id' => $createdById,
                    'status' => 1, // For Receive
                ];
            }

            $endpoint = '/item/masterdata-collection/get/1';
            if (strcasecmp($storeSubUnitShortName, 'BOH') === 0) {
                $endpoint = '/item/masterdata-collection/get/2';
            }
            $response = \Http::post(env('SCM_URL') . $endpoint, [
                'item_code_collection' => json_encode($existingItemCodes),
                'store_sub_unit_short_name' => $storeSubUnitShortName,
                'exception_item_code_collection' => json_encode(['FG0053', 'FG0055', 'FG0056', 'FG0057', 'FG0084']),
            ]);

            $data = $response->json()['success']['data'] ?? [];
            if (!empty($data)) {
                foreach ($data as $item) {
                    $stockInventoryItemsCount[] = [
                        'stock_inventory_count_id' => $stockInventoryCountId,
                        'item_code' => $item['item_code'],
                        'item_description' => $item['description'],
                        'item_category_name' => $item['item_category_label'],
                        'system_quantity' => 0,
                        'counted_quantity' => 0,
                        'discrepancy_quantity' => 0,
                        'created_at' => now(),
                        'created_by_id' => $createdById,
                        'updated_by_id' => $createdById,
                        'status' => 1, // For Receive
                    ];
                }
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

            if (!isset($array->getOriginalContent()['success'])) {
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
    public function onCancel(Request $request, $store_inventory_count_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $stockInventoryCountModel = StockInventoryCountModel::where('status', 0)->find($store_inventory_count_id);
            if (!$stockInventoryCountModel) {
                return $this->dataResponse('error', 404, __('msg.record_not_found'));
            }
            $stockInventoryCountModel->status = 3; // Set status to Cancelled
            $stockInventoryCountModel->updated_by_id = $createdById;
            $stockInventoryCountModel->save();
            DB::commit();
            return $this->dataResponse('success', 200, 'Cancelled Successfully');
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Cancel Failed', $exception->getMessage());
        }
    }
}

