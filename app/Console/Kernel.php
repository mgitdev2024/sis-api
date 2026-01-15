<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:create-store-receiving-inventory')->everyMinute();
        $schedule->command('app:delete-store-consolidation-cache')->dailyAt('00:00')->withoutOverlapping();
        $schedule->command('app:auto-posted-for-review-stock-count')->dailyAt('14:00')->withoutOverlapping();
        // $schedule->command('app:auto-posted-for-review-stock-count')->everyMinute()->withoutOverlapping(); //FOR TESTING PURPOSES ONLY
        // Add your queue worker
        $schedule->command('queue:work --stop-when-empty --timeout=0 --sleep=3')
            ->everyMinute()
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}