<?php

namespace App\Console\Commands\CronJob\ArchiveData;

use App\Models\ArchiveData\ArchivedProductionLogModel;
use App\Models\History\ProductionLogModel;
use App\Models\MOS\Production\ArchivedBatchesModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
use DB;
class ArchiveProductionLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:archive-production-log-command';

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
        $this->onArchiveProductionLogs();
    }

    protected function onArchiveProductionLogs()
    {
        DB::beginTransaction();
        try {
            $productionLogs = ProductionLogModel::all();

            if (count($productionLogs) > 0) {
                $logsToArchive = $productionLogs->map(function ($log) {
                    return [
                        'entity_model' => $log->entity_model,
                        'entity_id' => $log->entity_id,
                        'item_key' => $log->item_key,
                        'data' => $log->data,
                        'action' => $log->action,
                        'created_by_id' => $log->created_by_id,
                        'updated_by_id' => $log->updated_by_id,
                        'status' => $log->status,
                        'created_at' => $log->created_at,
                        'updated_at' => $log->updated_at,
                    ];
                })->toArray();
                ArchivedProductionLogModel::insert($logsToArchive);
                ProductionLogModel::whereIn('id', $productionLogs->pluck('id'))->delete();
                Log::info('Production Logs archived successfully');

                DB::commit();
            } else {
                Log::info('No Production Logs found');
                DB::rollback();

            }
        } catch (Exception $exception) {
            DB::rollback();
            throw $exception;
        }
    }
}
