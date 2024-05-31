<?php

namespace App\Traits;

use App\Http\Controllers\v1\History\ProductionLogController;
use Exception;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

trait ProductionLogTrait
{
    use ResponseTrait;
    public function createProductionLog($entityModel, $entityId, $data, $createdById, $action, $itemKey = null)
    {
        try {
            $productionLog = new ProductionLogController();
            $productionLogRequest = new Request([
                'created_by_id' => $createdById,
                'entity_model' => $entityModel,
                'entity_id' => $entityId,
                'item_key' => $itemKey,
                'data' => json_encode($data),
                'action' => $action
            ]);

            $productionLog->onCreate($productionLogRequest);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }
}
