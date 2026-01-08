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
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
                Log::info('No items to sync.');
                return;
            }

            // Fetch data from MGIOS
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
                throw new Exception('MGIOS API request failed: ' . $response->status());
            }

            $itemMasterData = $response->json();
            if (empty($itemMasterData)) {
                Log::info('No item master data returned from MGIOS.');
                return;
            }

            $updatedCount = 0;

            DB::beginTransaction();

            foreach ($itemMasterData as $itemCode => $data) {
                $itemCode = (string) $itemCode;
                $updates = [];

                $existingItem = StockInventoryModel::where('item_code', $itemCode)->first();
                if ($existingItem) {
                    if ($existingItem->item_description !== $data['long_name']) {
                        $updates['item_description'] = $data['long_name'];
                    }

                    if ($existingItem->item_category_name !== $data['category_name']) {
                        $updates['item_category_name'] = $data['category_name'];
                    }

                    if ($existingItem->uom !== $data['uom']) {
                        $updates['uom'] = $data['uom'];
                    }

                    if ($existingItem->is_sis_variant !== $data['is_sis_variant']) {
                        $updates['is_sis_variant'] = $data['is_sis_variant'];
                    }

                    if ($existingItem->is_viewable_item_request !== $data['is_viewable_item_request']) {
                        $updates['is_viewable_item_request'] = $data['is_viewable_item_request'];
                    }
                }
                if (!empty($updates)) {
                    $updates['updated_at'] = now();
                    StockInventoryModel::where('item_code', $itemCode)->update($updates);
                    $updatedCount++;
                }
            }

            DB::commit();

            if ($updatedCount > 0) {
                Log::info("Item sync completed. {$updatedCount} items updated.");
            } else {
                Log::info("Item sync completed. No changes detected.");
            }

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Item sync failed: " . $e->getMessage());
        }
    }

}
