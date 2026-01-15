<?php

namespace App\Console\Commands\CronJob\Stock;

use App\Http\Controllers\v1\Stock\StockInventoryItemCountController;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\Stock\StockInventoryCountModel;


use Log;

class AutoPostedForReviewStockCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-posted-for-review-stock-count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto post stock count records that are for review at 2PM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Stock Count Auto Post for Review Cron Job started at ' . Carbon::now()->toDateTimeString());
        // $this->onPostForReviewStockCount();
        $this->onAutoPostForReviewStockCount();
    }

    public function onAutoPostForReviewStockCount()
    {
        $now = Carbon::now();

        // 2PM cutoff today (exact)
        $cutoffTime = Carbon::today()->setTime(14, 0, 0);

        Log::info('Auto-post started at', [
            'now' => $now->toDateTimeString(),
            'cutoff' => $cutoffTime->toDateTimeString(),
        ]);

        $stockCounts = StockInventoryCountModel::where('status', 1)
            ->whereDate('created_at', Carbon::today())
            ->where('created_at', '<=', $cutoffTime)
            ->get();

        foreach ($stockCounts as $stockCount) {
            $request = \Request::create('', 'POST', [
                'created_by_id' => $stockCount->created_by_id,
                'store_code' => $stockCount->store_code,
                'store_sub_unit_short_name' => $stockCount->store_sub_unit_short_name,
                'stock_inventory_item_count_data' => null,
            ]);
            $stockInventoryCountId = $stockCount->id;

            Log::info('Auto-posting stock count ID: ' . $request);

            $stockInventoryItemCountController = new StockInventoryItemCountController();
            $stockInventoryItemCountController->onPost($request, $stockInventoryCountId);
        }

        Log::info('Auto-post completed', [
            'processed_count' => $stockCounts->count()
        ]);

        $this->info("Auto-posted {$stockCounts->count()} stock count records.");
    }

}
