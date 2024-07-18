<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Warehouse\WarehouseForPutAwayModel;
use App\Models\WMS\Warehouse\WarehousePutAwayModel;
use App\Traits\WMS\QueueSubLocationTrait;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use Exception;
use DB;

class WarehouseForPutAwayController extends Controller
{
    use WmsCrudOperationsTrait, QueueSubLocationTrait;
    public function getRules()
    {
        return [
            'created_by_id' => 'required',
            'production_items' => 'required|json',
            'warehouse_receiving_reference_number' => 'required|exists:wms_warehouse_receiving,reference_number',
            'warehouse_put_away_id' => 'required|exists:wms_warehouse_put_away,id',
            'item_code' => 'required|exists:wms_item_masterdata,item_code',
            'sub_location_id' => 'nullable|exists:wms_storage_sub_locations,id',
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->getRules());
        try {
            $warehouseForPutAway = WarehouseForPutAwayModel::where([
                'warehouse_put_away_id' => $fields['warehouse_put_away_id'],
                'item_code' => $fields['item_code'],
            ])->first();
            $data = null;
            if (!$warehouseForPutAway) {
                $record = new WarehouseForPutAwayModel();
                $record->fill($fields);
                $record->save();
                $data = $record;
            } else {
                $warehouseForPutAway->production_items = $fields['production_items'];
                $warehouseForPutAway->save();
                $data = $warehouseForPutAway;
            }

            return $this->dataResponse('success', 201, 'Warehouse For Put Away ' . __('msg.create_success'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Warehouse For Put Away ' . __('msg.create_failed'));
        }
    }

    public function onUpdate(Request $request, $warehouse_put_away_id)
    {
        $fields = $request->validate([
            'warehouse_receiving_reference_number' => 'required|exists:wms_warehouse_receiving,reference_number',
            'item_code' => 'required|exists:wms_item_masterdata,item_code',
            'sub_location_id' => 'required|exists:wms_storage_sub_locations,id',
            'layer_level' => 'required|integer',
            'updated_by_id' => 'required'
        ]);
        try {
            $warehouseForPutAwayModel = WarehouseForPutAwayModel::where('warehouse_put_away_id', $warehouse_put_away_id)
                ->where('warehouse_receiving_reference_number', $fields['warehouse_receiving_reference_number'])
                ->where('item_code', $fields['item_code'])
                ->orderBy('id', 'DESC')
                ->first();
            if ($warehouseForPutAwayModel) {
                $permanentSubLocation = SubLocationModel::where('is_permanent', 1)
                    ->where('id', $fields['sub_location_id'])
                    ->first();
                if (!$permanentSubLocation) {
                    $message = [
                        'error_type' => 'incorrect_storage',
                        'message' => 'Sub Location does not exist or incorrect storage type'
                    ];
                    $data['sub_location_error_message'] = $message;
                    return $this->dataResponse('success', 200, __('msg.update_failed'), $data);
                }

                $itemMasterdata = $warehouseForPutAwayModel->itemMasterdata;
                $isStorageTypeMismatch = !($permanentSubLocation->zone->storage_type_id === $itemMasterdata->storage_type_id);

                $data = [];


                if ($isStorageTypeMismatch) {
                    $message = [
                        'error_type' => 'storage_mismatch',
                        'storage_type' => $itemMasterdata->storage_type_label['long_name']
                    ];
                    $data['sub_location_error_message'] = $message;
                    return $this->dataResponse('success', 200, __('msg.update_failed'), $data);
                }
                $queuedSubLocationAvailability = $this->onCheckAvailability($permanentSubLocation->id, true, $fields['layer_level'], $fields['updated_by_id']);
                if ($queuedSubLocationAvailability) {
                    $message = [
                        'error_type' => 'storage_occupied',
                        'message' => SubLocationModel::onGenerateStorageCode($permanentSubLocation->id, $fields['layer_level'])['storage_code'] . ' is in use.'
                    ];
                    $data['sub_location_error_message'] = $message;
                    return $this->dataResponse('success', 200, __('msg.update_failed'), $data);

                }

                $checkStorageSpace = $this->onCheckStorageSpace($permanentSubLocation->id, $fields['layer_level'], 1);
                $isStorageFull = !$checkStorageSpace['is_full'];
                if ($isStorageFull) {
                    $message = [
                        'error_type' => 'storage_full',
                        'current_size' => $checkStorageSpace['current_size'],
                        'allocated_space' => $checkStorageSpace['allocated_space'],
                        'remaining_space' => $checkStorageSpace['remaining_space']
                    ];
                    $data['sub_location_error_message'] = $message;
                    return $this->dataResponse('success', 200, __('msg.update_failed'), $data);
                }
                $warehouseForPutAwayModel->sub_location_id = $permanentSubLocation->id;
                $warehouseForPutAwayModel->layer_level = $fields['layer_level'];
                $warehouseForPutAwayModel->save();
                return $this->dataResponse('success', 200, __('msg.update_success'));

            }
            return $this->dataResponse('success', 200, __('msg.record_not_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());

        }
    }

    public function onTransferItems(Request $request, $warehouse_put_away_id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'scanned_items' => 'required|json',
        ]);

        try {
            DB::beginTransaction();
            $warehouseForPutAwayModel = WarehouseForPutAwayModel::where('warehouse_put_away_id', $warehouse_put_away_id)->first();
            if (!$warehouseForPutAwayModel) {
                return $this->dataResponse('success', 200, __('msg.record_not_found'));
            }

            $scannedItems = json_decode($fields['scanned_items'], true);
            $transferItemsArr = [];
            foreach ($scannedItems as $value) {
                $inclusionArray = [3];
                $productionItemModel = ProductionItemModel::where('production_batch_id', $value['bid'])->first();
                $productionItems = json_decode($productionItemModel->produced_items, true);
                $flag = $this->onItemCheckHoldInactiveDone($productionItems, $value['sticker_no'], $inclusionArray, []);

                if (!$flag) {
                    continue;
                }
                $productionItems[$value['sticker_no']]['status'] = 3.1;
                $productionItemModel->produced_items = json_encode($productionItems);
                $productionItemModel->save();
                $this->createProductionLog(ProductionItemModel::class, $productionItemModel->id, $productionItems[$value['sticker_no']], $fields['created_by_id'], 1, $value['sticker_no']);

                $warehousePutAwayModel = WarehousePutAwayModel::find($warehouse_put_away_id);
                $warehousePutAwayItems = json_decode($warehousePutAwayModel->production_items, true);
                foreach ($warehousePutAwayItems as &$warehouseValue) {
                    if (($warehouseValue['bid'] == $value['bid']) && ($warehouseValue['sticker_no'] == $value['sticker_no'])) {
                        $warehouseValue['status'] = 3.1;
                    }
                    unset($warehouseValue);
                }
                $warehousePutAwayModel->production_items = json_encode($warehousePutAwayItems);
                $warehousePutAwayModel->save();
                $transferItemsArr[] = $value;
            }
            if (count($transferItemsArr) > 0) {
                $warehouseForPutAwayModel->transfer_items = json_encode($transferItemsArr);
                $warehouseForPutAwayModel->save();
            }


            DB::commit();
            return $this->dataResponse('success', 201, __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getmessage());
        }
    }

    public function onItemCheckHoldInactiveDone($producedItems, $itemKey, $inclusionArray, $exclusionArray)
    {
        $inArrayFlag = count($inclusionArray) > 0 ?
            in_array($producedItems[$itemKey]['status'], $inclusionArray) :
            !in_array($producedItems[$itemKey]['status'], $exclusionArray);
        return $producedItems[$itemKey]['sticker_status'] != 0 && $inArrayFlag;
    }
    public function onGetCurrent($warehouse_put_away_id, $created_by_id)
    {
        $warehouseForReceive = WarehouseForPutAwayModel::where('warehouse_put_away_id', $warehouse_put_away_id)
            ->where('created_by_id', $created_by_id)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->first();

        if ($warehouseForReceive) {
            return $this->dataResponse('success', 200, __('msg.record_found'), $warehouseForReceive);
        }
        return $this->dataResponse('success', 200, __('msg.record_not_found'), $warehouseForReceive);
    }
    public function onDelete($warehouse_put_away_id)
    {
        try {
            $warehouseForPutAway = WarehouseForPutAwayModel::where('warehouse_put_away_id', $warehouse_put_away_id)->firstOrFail();
            $warehousePutAway = $warehouseForPutAway->warehousePutAway;
            $warehousePutAwayItems = json_decode($warehousePutAway->production_items, true);
            if ($warehouseForPutAway->count() > 0) {
                $productionItemsWarehouse = json_decode($warehouseForPutAway->production_items, true);
                foreach ($productionItemsWarehouse as &$items) {
                    $productionItemOrder = ProductionItemModel::where('production_batch_id', $items['bid'])->first();
                    $producedItems = json_decode($productionItemOrder->produced_items, true);
                    $producedItems[$items['sticker_no']]['status'] = 3;
                    $productionItemOrder->produced_items = json_encode($producedItems);
                    $productionItemOrder->save();
                    $this->createProductionLog(ProductionItemModel::class, $productionItemOrder->id, $producedItems[$items['sticker_no']], $warehouseForPutAway->created_by_id, 3, $items['sticker_no']);

                    foreach ($warehousePutAwayItems as &$value) {
                        if (($value['bid'] == $items['bid']) && ($value['sticker_no'] == $items['sticker_no'])) {
                            $value['status'] = 3;
                        }
                    }
                }
                $warehousePutAway->production_items = json_encode($warehousePutAwayItems);
                $warehousePutAway->save();
                $warehouseForPutAway->delete();
                return $this->dataResponse('success', 200, __('msg.delete_success'));
            }

            return $this->dataResponse('success', 200, __('msg.record_not_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        }
    }
}
