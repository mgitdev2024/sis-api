<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use Illuminate\Http\Request;
use App\Traits\WMS\WmsCrudOperationsTrait;

class QueuedTemporaryStorageController extends Controller
{
    use WmsCrudOperationsTrait;
    public function onGetCurrent($sub_location_id)
    {
        $whereFields = [
            'sub_location_id' => $sub_location_id
        ];
        $this->readCurrentRecord(QueuedTemporaryStorageModel::class, null, $whereFields, null, null, 'Queued Temporary Storage');
    }
}
