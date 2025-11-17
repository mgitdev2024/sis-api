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
    use ResponseTrait;
    use CrudOperationsTrait;
    public function onGetById($store_inventory_count_id = null)
    {
        try {
            if ($store_inventory_count_id) {
                $stockInventoryCountModel = StockInventoryCountModel::findOrFail($store_inventory_count_id);
                $subUnit = $stockInventoryCountModel->store_sub_unit_short_name;

                // 1️⃣ Get local stock count items (indexed by item_code)
                $localItems = $stockInventoryCountModel->stockInventoryItemsCount()
                    ->get()
                    ->keyBy('item_code');

                $itemCodes = $localItems->keys();

                // 2️⃣ Fetch API data
                $response = Http::withHeaders([
                    'x-api-key' => config('apikeys.mgios_api_key'),
                ])->post(
                    config('apiurls.mgios.url') . config('apiurls.mgios.public_get_item_by_department') . $subUnit,
                    ['item_code_collection' => json_encode($itemCodes)]
                );

                if (!$response->successful()) {
                    return $this->dataResponse('error', 500, 'Failed to fetch item data from API');
                }

                $apiData = $response->json();

                // 3️⃣ Merge local values into the nested API data (retain structure)
                foreach ($apiData as $department => &$categories) {
                    foreach ($categories as $category => &$items) {
                        foreach ($items as &$item) {
                            $apiItemData = $item;
                            $code = $item['item_code'];
                            if (isset($localItems[$code])) {
                                $local = $localItems[$code];
                                $item = $local;
                                $item['uom'] = $apiItemData['uom'] ?? null;
                            }
                        }
                    }
                }
                unset($categories, $items, $item); // good practice when modifying by reference
                $data = [
                    'stock_inventory_count' => $stockInventoryCountModel,
                    'stock_inventory_items_count' => $apiData
                ];
                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onUpdate(Request $request, $store_inventory_count_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'store_code' => 'required|string|max:50',
            'store_sub_unit_short_name' => 'required|string|max:50',
            'stock_inventory_count_data' => 'required|json' // [{"ic":"CR 12","cq":12},{"ic":"TAS WH","cq":1}]
        ]);

        try {
            DB::beginTransaction();

            // Pre-decode and validate JSON
            $stockInventoryCountData = json_decode($fields['stock_inventory_count_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON format in stock_inventory_count_data');
            }

            $createdById = $fields['created_by_id'];
            $now = now();

            // 1️⃣ Update main inventory count record (single query)
            $stockInventoryCountModel = StockInventoryCountModel::find($store_inventory_count_id);
            if (!$stockInventoryCountModel) {
                throw new Exception('Stock inventory count not found');
            }

            $stockInventoryCountModel->update([
                'status' => 1, // For Review
                'updated_by_id' => $createdById,
                'reviewed_at' => $now,
                'reviewed_by_id' => $createdById
            ]);

            // 2️⃣ Extract item codes for bulk fetch
            $itemCodes = collect($stockInventoryCountData)->pluck('ic')->filter()->unique()->values()->all();

            if (empty($itemCodes)) {
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'));
            }

            // 3️⃣ Bulk fetch all relevant inventory items (single query)
            $stockInventoryItems = StockInventoryItemCountModel::where('stock_inventory_count_id', $store_inventory_count_id)
                ->whereIn('item_code', $itemCodes)
                ->get()
                ->keyBy('item_code'); // Index by item_code for O(1) lookup

            // 4️⃣ Prepare batch update data
            $batchUpdates = [];
            $updatedItemIds = [];

            foreach ($stockInventoryCountData as $item) {
                $itemCode = $item['ic'] ?? null;
                $countedQuantity = is_numeric($item['cq']) ? (float) $item['cq'] : 0;

                if (!$itemCode || !isset($stockInventoryItems[$itemCode])) {
                    continue; // Skip invalid or non-existent items
                }

                $stockItem = $stockInventoryItems[$itemCode];
                $discrepancyQuantity = $stockItem->system_quantity - $countedQuantity;

                $batchUpdates[] = [
                    'id' => $stockItem->id,
                    'counted_quantity' => $countedQuantity,
                    'discrepancy_quantity' => $discrepancyQuantity,
                    'status' => 1, // For Review
                    'updated_by_id' => $createdById,
                    'updated_at' => $now,
                ];

                $updatedItemIds[] = $stockItem->id;
            }

            // 5️⃣ Process updates in chunks for very large datasets
            $chunkSize = 500; // Adjust based on your database capabilities
            $chunks = array_chunk($batchUpdates, $chunkSize);

            foreach ($chunks as $chunk) {
                $cases = [];
                $ids = [];

                foreach ($chunk as $update) {
                    $id = $update['id'];
                    $ids[] = $id;

                    $cases['counted_quantity'][] = "WHEN {$id} THEN {$update['counted_quantity']}";
                    $cases['discrepancy_quantity'][] = "WHEN {$id} THEN {$update['discrepancy_quantity']}";
                    $cases['status'][] = "WHEN {$id} THEN {$update['status']}";
                    $cases['updated_by_id'][] = "WHEN {$id} THEN {$update['updated_by_id']}";
                }

                if (!empty($ids)) {
                    $idsString = implode(',', $ids);
                    $countedQuantityCases = implode(' ', $cases['counted_quantity']);
                    $discrepancyQuantityCases = implode(' ', $cases['discrepancy_quantity']);
                    $statusCases = implode(' ', $cases['status']);
                    $updatedByCases = implode(' ', $cases['updated_by_id']);

                    // Single bulk UPDATE query per chunk
                    DB::statement("
                        UPDATE stock_inventory_items_count
                        SET
                            counted_quantity = CASE id {$countedQuantityCases} END,
                            discrepancy_quantity = CASE id {$discrepancyQuantityCases} END,
                            status = CASE id {$statusCases} END,
                            updated_by_id = CASE id {$updatedByCases} END,
                            updated_at = ?
                        WHERE id IN ({$idsString})
                    ", [$now]);
                }
            }

            DB::commit();

            $updatedCount = count($batchUpdates);
            $totalReceived = count($stockInventoryCountData);

            return $this->dataResponse('success', 200, __('msg.update_success'), [
                'updated_items' => $updatedCount,
                'total_received' => $totalReceived,
                'processing_time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2)
            ]);

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return $this->dataResponse('error', 422, 'Validation Error: ' . $e->getMessage());
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 500, __('msg.update_failed'), [
                'error' => $exception->getMessage(),
                'line' => $exception->getLine()
            ]);
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
