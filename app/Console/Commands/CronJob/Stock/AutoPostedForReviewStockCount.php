<?php

namespace App\Console\Commands\CronJob\Stock;

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
        $this->onPostForReviewStockCount();
    }

    public function onPostForReviewStockCount()
    {
        $now = Carbon::now();

        // Extra safety: ensure 2PM logic
        if ($now->format('H:i') !== '14:00') {
            return 0;
        }

        $posted = StockInventoryCountModel::where('status', 1)
            ->whereDate('created_at', now()->toDateString())
            ->update([
                'status' => 2,
                'posted_at' => now(),
                'posted_by_id' => '0000',
            ]);
        if ($posted > 0) {
            Log::info("Auto-posted {$posted} stock count records for review.");
        } else {
            Log::info('No stock count records found for auto-posting.');
        }

        Log::info('Auto-post stock count cron executed', [
            'posted_count' => $posted,
        ]);
        $this->info("Auto-posted {$posted} stock count records.");
    }
}