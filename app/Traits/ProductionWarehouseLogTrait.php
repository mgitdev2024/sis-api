<?php

namespace App\Traits;

use App\Http\Controllers\v1\History\ProductionWarehouseLogController;
use Exception;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

trait ProductionWarehouseLogTrait
{
    use ResponseTrait;
    public function createProductionWarehouseLog($referenceModel, $referenceId, $entityModel, $entityId, $data, $createdById, $action, $itemKey = null)
    {
        try {
            $productionWarehouseLog = new ProductionWarehouseLogController();
            $productionWarehouseLogRequest = new Request([
                'created_by_id' => $createdById,
                'reference_model' => $referenceModel,
                'reference_id' => $referenceId,
                'entity_model' => $entityModel,
                'entity_id' => $entityId,
                'item_key' => $itemKey,
                'data' => json_encode($data),
                'action' => $action
            ]);

            $productionWarehouseLog->onCreate($productionWarehouseLogRequest);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception);
        }
    }
}
