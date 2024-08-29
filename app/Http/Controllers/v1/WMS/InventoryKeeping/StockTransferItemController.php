<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping;

use App\Http\Controllers\Controller;
use App\Traits\WMS\InventoryMovementTrait;
use Illuminate\Http\Request;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Exception, DB;
class StockTransferItemController extends Controller
{
    use WmsCrudOperationsTrait, InventoryMovementTrait;

    // public function onGetZoneItemList($zone_id)
    // {
    //     try {
    //         $zoneItems = $this->onGetZoneStoredItems($zone_id);
    //         return $this->dataResponse('success', 200, 'Zone Items', $zoneItems);
    //     } catch (Exception $exception) {
    //         return $this->dataResponse('error', 400, 'Inventory Movement ' . __('msg.record_not_found'));
    //     }
    // }
}
