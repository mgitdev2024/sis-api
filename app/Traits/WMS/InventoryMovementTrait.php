<?php

namespace App\Traits\WMS;

use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use Exception;
use App\Traits\ResponseTrait;
use DB;

trait InventoryMovementTrait
{
    use ResponseTrait;

    public function onGetReceiveItems($createdAt, $statusType)
    {
        try {
            $warehouseReceiving = WarehouseReceivingModel::whereDate('created_at', $createdAt)->first();
            if ($warehouseReceiving) {
                $quantity = $warehouseReceiving->quantity;
                $receivingQuantity = $warehouseReceiving->received_quantity;
                $substandardQuantity = $warehouseReceiving->substandard_quantity;
                $forReceiveQuantity = 0;
                if (strcasecmp($statusType, 'to receive') == 0) {
                    $forReceiveQuantity = $quantity - ($receivingQuantity + $substandardQuantity);
                }
                // Add other conditions here

                return $forReceiveQuantity;
            } else {
                return 0;
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Inventory Movement ' . __('msg.record_not_found'));
        }
    }

    // Add other methods for Put Away, Stock Transfer, and Distribution here
}

