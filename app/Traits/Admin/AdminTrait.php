<?php

namespace App\Traits\Admin;

use App\Models\User;
use Exception;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;

trait AdminTrait
{
    use ResponseTrait;

    public function onCreateAdminLogs($entityId, $entityModel, $data, $action, $createdById)
    {
        try {

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception);
        }
    }
}

