<?php

namespace App\Http\Controllers\v1\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StockInventoryCountModel;
use App\Models\Stock\StockInventoryItemCountModel;
use App\Models\Stock\StockInventoryModel;
use App\Models\Stock\StockLogModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use DB;
use Exception;

class StockInventoryItemCountController extends Controller
{
    use ResponseTrait, CrudOperationsTrait;
    public function onGetById($store_inventory_count_id = null)
    {
        try {
            if ($store_inventory_count_id) {
                $stockInventoryCountModel = StockInventoryCountModel::findOrFail($store_inventory_count_id);
                $stockInventoryItemCountModel = $stockInventoryCountModel->stockInventoryItemsCount()
                    ->orderBy('system_quantity', 'DESC')
                    ->get()->groupBy('item_category_name');
                $warehouseCodes = $stockInventoryItemCountModel
                    ->flatten(1)
                    ->pluck('item_category_name')
                    ->unique()->values(); // optional, reindex array

                if (count($warehouseCodes) > 0) {
                    $stockItemCountDepartment = [];
                    // Fetch items from external API based on warehouse codes
                    $response = Http::withHeaders([
                        'x-api-key' => config('apikeys.mgios_api_key'),
                    ])->post(config('apiurls.mgios.url') . config('apiurls.mgios.public_get_department_by_category'), [
                                'category_name_collection' => json_encode($warehouseCodes),
                            ]);
                    if ($response->successful()) {
                        $departmentData = collect($response->json()); // make departmentData a collection for easier lookup

                        foreach ($stockInventoryItemCountModel as $categoryKey => $data) {
                            if (!isset($departmentData[$categoryKey])) {
                                // Register or handle unassigned category
                                $stockItemCountDepartment[$categoryKey] = $data->toArray();
                            } else {
                                // Assign under the department short name
                                $deptShortName = $departmentData[$categoryKey]['short_name'];
                                $stockItemCountDepartment[$deptShortName][$categoryKey] = $data->toArray();
                            }
                        }
                        $stockInventoryItemCountModel = $stockItemCountDepartment;
                    }
                }

                $data = [
                    'stock_inventory_count' => $stockInventoryCountModel,
                    'stock_inventory_items_count' => $stockInventoryItemCountModel
                ];
                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            }

        } catch (Exception $exception) {
            dd($exception);
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onUpdate(Request $request, $store_inventory_count_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'store_code' => 'required',
            'store_sub_unit_short_name' => 'required',
            'stock_inventory_count_data' => 'required' // [{"ic":"CR 12","cq":12},{"ic":"TAS WH","cq":1}]
        ]);

        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $stockInventoryCountData = json_decode($fields['stock_inventory_count_data'], true);

            $stockInventoryCountModel = StockInventoryCountModel::find($store_inventory_count_id);
            if ($stockInventoryCountModel) {
                $stockInventoryCountModel->update([
                    'status' => 1, // For Review
                    'updated_by_id' => $createdById,
                    'reviewed_at' => now(),
                    'reviewed_by_id' => $createdById
                ]);
            }
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
                        'status' => 1, // For Review
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

    public function onPost(Request $request, $store_inventory_count_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'store_code' => 'required',
            'store_sub_unit_short_name' => 'required',
            'stock_inventory_item_count_data' => 'nullable' // {"CR 12":"Nahulog","TAS WH":"Nawala"}
        ]);

        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];

            $stockInventoryCountModel = StockInventoryCountModel::find($store_inventory_count_id);
            if ($stockInventoryCountModel) {
                $stockInventoryCountModel->update([
                    'status' => 2, // Post
                    'updated_by_id' => $createdById,
                    'posted_at' => now(),
                    'posted_by_id' => $createdById
                ]);
            }
            $stockInventoryItemCountModel = StockInventoryItemCountModel::where([
                'stock_inventory_count_id' => $store_inventory_count_id,
            ])->get();

            foreach ($stockInventoryItemCountModel as $item) {
                $stockInventoryCountData = json_decode($fields['stock_inventory_item_count_data'] ?? '[]', true);
                $item->remarks = $stockInventoryCountData[$item->item_code] ?? null;
                $item->save();

                $countedQuantity = $item->counted_quantity;
                // Update the stock inventory
                $stockInventoryModel = StockInventoryModel::where([
                    'store_code' => $fields['store_code'],
                    'store_sub_unit_short_name' => $fields['store_sub_unit_short_name'],
                    'item_code' => $item->item_code,
                ])->first();

                if ($stockInventoryModel) {
                    $stockInventoryModel->update([
                        'stock_count' => $countedQuantity,
                        'updated_by_id' => $createdById,
                    ]);
                } else {
                    // If the stock inventory does not exist, create a new one
                    StockInventoryModel::create([
                        'store_code' => $fields['store_code'],
                        'store_sub_unit_short_name' => $fields['store_sub_unit_short_name'],
                        'item_code' => $item->item_code,
                        'item_description' => $item->item_description,
                        'item_category_name' => $item->item_category_name,
                        'stock_count' => $countedQuantity,
                        'created_by_id' => $createdById,
                        'updated_by_id' => $createdById,
                    ]);
                }

                $latestLog = StockLogModel::where([
                    'store_code' => $fields['store_code'],
                    'store_sub_unit_short_name' => $fields['store_sub_unit_short_name'],
                    'item_code' => $item->item_code,
                ])->orderBy('id', 'DESC')->first();

                $data = [
                    'reference_number' => $item->stockInventoryCount->reference_number,
                    'store_code' => $fields['store_code'],
                    'store_sub_unit_short_name' => $fields['store_sub_unit_short_name'],
                    'item_code' => $latestLog?->item_code ?? $item->item_code,
                    'item_description' => $latestLog?->item_description ?? $item->item_description,
                    'item_category_name' => $latestLog?->item_category_name ?? $item->item_category_name,
                    'quantity' => 0,
                    'initial_stock' => $latestLog?->final_stock ?? 0,
                    'final_stock' => $countedQuantity,
                    'transaction_type' => 'adjustment',
                    'created_by_id' => $createdById,
                ];

                StockLogModel::create($data);

            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.update_failed'), $exception->getMessage());
        }
    }
}
