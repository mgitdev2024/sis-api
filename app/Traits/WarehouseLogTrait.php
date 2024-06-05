<?php

namespace App\Traits;

use App\Http\Controllers\v1\History\WarehouseLogController;
use Exception;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

trait WarehouseLogTrait
{
    use ResponseTrait;
    public function createWarehouseLog($referenceModel, $referenceId, $entityModel, $entityId, $data, $createdById, $action, $itemKey = null)
    {
        try {
            $warehouseLog = new WarehouseLogController();
            $warehouseLogRequest = new Request([
                'created_by_id' => $createdById,
                'reference_model' => $referenceModel,
                'reference_id' => $referenceId,
                'entity_model' => $entityModel,
                'entity_id' => $entityId,
                'item_key' => $itemKey,
                'data' => json_encode($data),
                'action' => $action
            ]);

            $warehouseLog->onCreate($warehouseLogRequest);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception);
        }
    }
}
