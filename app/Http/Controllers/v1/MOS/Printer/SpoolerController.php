<?php

namespace App\Http\Controllers\v1\MOS\Printer;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
class SpoolerController extends Controller
{
    use ResponseTrait;
    public function checkPendingPrintJobs()
    {
        // Command to check the print queue for pending jobs
        $command = 'wmic printjob get jobid, status';  // Query the print jobs

        // Execute the command
        $output = shell_exec($command);
        
        $data = ['status' => 0 ];

        // Check if there are any pending print jobs
        if (strpos($output, 'Status') !== false && strpos($output, 'Printing') === false) {
            $data['status'] = 1;
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } else {
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);

        }
    }
}
