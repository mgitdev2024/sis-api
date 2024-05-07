<?php

namespace App\Traits;

use App\Http\Controllers\v1\History\ProductionHistoricalLogController;
use Exception;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

trait ProductionHistoricalLogTrait
{
    use ResponseTrait;
    public function createProductionHistoricalLog($entityModel, $entityId, $data, $createdById, $action, $itemKey = null)
    {
        try {
            $productionHistoricalLog = new ProductionHistoricalLogController();
            $productionHistoricalRequest = new Request([
                'created_by_id' => $createdById,
                'entity_model' => $entityModel,
                'entity_id' => $entityId,
                'item_key' => $itemKey,
                'data' => json_encode($data),
                'action' => $action
            ]);
            $productionHistoricalLog->onCreate($productionHistoricalRequest);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }
}
