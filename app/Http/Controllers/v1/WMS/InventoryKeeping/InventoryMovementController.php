<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping;

use App\Http\Controllers\Controller;
use App\Traits\WMS\InventoryMovementTrait;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use Exception;
class InventoryMovementController extends Controller
{
    use WmsCrudOperationsTrait, InventoryMovementTrait;

    public function onGetInventoryMovementStats($date)
    {
        try {
            // To Receive
            $formattedDate = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$formattedDate->format('Y-m-d') === $date) {
                return $this->dataResponse('error', 200, 'Invalid date');
            }
            $toReceiveQuantity = $this->onGetReceiveItems($date, 'to receive');
            // For Put Away

            // Stock Transfer

            // For Distribution
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Inventory Movement ' . __('msg.record_not_found'));
        }
    }
}
