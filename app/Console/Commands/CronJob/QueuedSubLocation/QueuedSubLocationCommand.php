<?php

namespace App\Console\Commands\CronJob\QueuedSubLocation;
use App\Models\WMS\Storage\QueuedSubLocationModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
use DB;
use Carbon\Carbon;
class QueuedSubLocationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:queued-sub-location-command';

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
        $this->cleanOldSubLocations();
    }

    protected function cleanOldSubLocations()
    {
        try {
            DB::beginTransaction();
            $latestRecords = QueuedSubLocationModel::select('sub_location_id', DB::raw('MAX(id) as latest_id'))
                ->groupBy('sub_location_id')
                ->pluck('latest_id');
            QueuedSubLocationModel::whereNotIn('id', $latestRecords)->delete();
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
        }

    }
}
