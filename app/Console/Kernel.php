<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:archive-production-log-command')->everyMinute();
        $schedule->command('app:queued-sub-location-command')->everyMinute();
        // ->cron('0 0 1,15 * *'); // Runs every 1st and 15th of the month
        /*
        $schedule->command('app:your-command')->everyMinute(); // Runs every minute
        $schedule->command('app:your-command')->hourly(); // Runs every hour
        $schedule->command('app:your-command')->dailyAt('13:00'); // Runs daily at 1 PM
        $schedule->command('app:your-command')->cron('* /15 * * * *'); // Custom cron expression (every 15 minutes)
         */
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
