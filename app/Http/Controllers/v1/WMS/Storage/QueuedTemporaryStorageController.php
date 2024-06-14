<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use App\Traits\WMS\WmsCrudOperationsTrait;

class QueuedTemporaryStorageController extends Controller
{
    use WmsCrudOperationsTrait, QueueSubLocationTrait;
    public function onGetCurrent($sub_location_id)
    {
        $whereFields = [
            'sub_location_id' => $sub_location_id
        ];
        $this->readCurrentRecord(QueuedTemporaryStorageModel::class, null, $whereFields, null, null, 'Queued Temporary Storage');
    }

    public function onGetStatus($id)
    {
        $data = $this->onCheckAvailability($id, false);
        return $this->dataResponse('success', 200, __('msg.record_found'), $data);
    }
}
