<?php

namespace App\Console\Commands\CronJob\Store;

use App\Models\Store\StoreConsolidationCacheModel;
use App\Models\Store\StoreReceivingInventoryItemModel;
use App\Models\Store\StoreReceivingInventoryModel;
use Http;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
use DB;
use Carbon\Carbon;
class DeleteStoreConsolidationCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-store-consolidation-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Deleting store consolidation cache...');
        $this->onDeleteStoreConsolidationCache();
    }

    protected function onDeleteStoreConsolidationCache()
    {
        try {
            $threeDaysAgo = Carbon::now()->subDays(3);
            $storeConsolidationCache = StoreConsolidationCacheModel::where('status', 0)
                ->where('created_at', '<=', $threeDaysAgo)
                ->get();

            if ($storeConsolidationCache->isEmpty()) {
                Log::info('No pending consolidation cache found older than three days.');
                return;
            }

            DB::beginTransaction();
            foreach ($storeConsolidationCache as $cache) {
                $cache->delete();
            }
            DB::commit();
            Log::info('Store consolidation cache older than three days deleted successfully.');
            return;
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error('Failed to delete store consolidation cache', [
                'error' => $exception->getMessage()
            ]);
            return;
        }
    }
}
