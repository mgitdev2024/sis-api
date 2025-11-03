<?php

namespace App\Jobs\Stock;

use App\Models\Stock\StockInventoryModel;
use Cache;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use DB;
use Exception;
use Log;
class SyncItemListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Synchronization of item list started.');
        $this->onSyncItemList();
    }

    private function onSyncItemList()
    {
        try {
            $itemCodes = StockInventoryModel::distinct()->pluck('item_code')->toArray();

            if (empty($itemCodes)) {
                throw new Exception('No items to sync.');
            }

            $response = Http::withHeaders([
                'x-api-key' => config('apikeys.mgios_api_key'),
            ])->post(
                    config('apiurls.mgios.url') . config('apiurls.mgios.public_item_masterdata_collection_get'),
                    [
                        'item_code_collection' => json_encode($itemCodes),
                        'is_key_by' => true,
                    ]
                );

            if ($response->failed()) {
                throw new Exception('MGIOS API request failed with status ' . $response->status());
            }

            $itemMasterData = $response->json();
            if (empty($itemMasterData)) {
                throw new Exception('No item master data returned from MGIOS.');
            }

            DB::beginTransaction();

            $caseDescription = '';
            $caseCategory = '';
            $bindings = [];
            $itemCodesToUpdate = [];

            // Fetch current local data for comparison (indexed by item_code)
            $localItems = StockInventoryModel::whereIn('item_code', $itemCodes)
                ->pluck('item_description', 'item_code')
                ->toArray();

            $localCategories = StockInventoryModel::whereIn('item_code', $itemCodes)
                ->pluck('item_category_name', 'item_code')
                ->toArray();

            foreach ($itemMasterData as $itemCode => $data) {
                $itemCode = (string) $itemCode;
                $description = $data['long_name'] ?? null;
                $categoryName = $data['category_name'] ?? null;

                // Get current local values
                $currentDesc = $localItems[$itemCode] ?? null;
                $currentCat = $localCategories[$itemCode] ?? null;

                // Skip if both are identical (no change)
                if (
                    ($description === null || $description === $currentDesc) &&
                    ($categoryName === null || $categoryName === $currentCat)
                ) {
                    continue;
                }

                // Build CASE for item_description (only if changed)
                if ($description !== null && $description !== $currentDesc) {
                    $caseDescription .= " WHEN ? THEN ? ";
                    $bindings[] = $itemCode;
                    $bindings[] = $description;
                }

                // Build CASE for category (only if changed)
                if ($categoryName !== null && $categoryName !== $currentCat) {
                    $caseCategory .= " WHEN ? THEN ? ";
                    $bindings[] = $itemCode;
                    $bindings[] = $categoryName;
                }

                $itemCodesToUpdate[] = $itemCode;
            }

            if (!empty($itemCodesToUpdate)) {
                $placeholders = implode(',', array_fill(0, count($itemCodesToUpdate), '?'));

                $sql = "
                UPDATE stock_inventories
                SET
                    item_description = CASE item_code
                        {$caseDescription}
                        ELSE item_description
                    END,
                    item_category_name = CASE item_code
                        {$caseCategory}
                        ELSE item_category_name
                    END,
                    updated_at = NOW()
                WHERE item_code IN ({$placeholders})
            ";

                DB::update($sql, array_merge($bindings, $itemCodesToUpdate));
            }

            DB::commit();

            $updatedCount = count($itemCodesToUpdate);
            if (count($itemCodesToUpdate) > 0) {
                Log::info("Synchronization of item list completed. {$updatedCount} items updated.");
            } else {
                Log::info("Synchronization of item list completed. No items were updated.");

            }

        } catch (Exception $exception) {
            DB::rollBack();
            Log::error("Synchronization of item list failed: " . $exception->getMessage());
        }
    }
}
