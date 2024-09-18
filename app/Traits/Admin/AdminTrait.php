<?php

namespace App\Traits\Admin;

use App\Models\Admin\System\SystemLogModel;
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
            $systemLog = new SystemLogModel();
            $systemLog->entity_id = $entityId;
            $systemLog->entity_model = $entityModel;
            $systemLog->data = json_encode($data);
            $systemLog->action = $action;
            $systemLog->created_by_id = $createdById;
            $systemLog->save();

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception);
        }
    }
}

