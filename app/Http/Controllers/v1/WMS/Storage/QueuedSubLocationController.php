<?php

namespace App\Http\Controllers\v1\WMS\Storage;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use Exception;

class QueuedSubLocationController extends Controller
{
    use QueueSubLocationTrait, ResponseTrait;
    public function onGetCurrent($sub_location_id, $layer)
    {
        try {
            $this->onGetSubLocationDetails($sub_location_id, $layer, true);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());

        }
    }
}
