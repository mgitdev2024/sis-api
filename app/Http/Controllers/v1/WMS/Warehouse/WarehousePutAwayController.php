<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\QualityAssurance\SubStandardItemController;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\Storage\QueuedSubLocationModel;
use App\Models\WMS\Storage\QueuedTemporaryStorageModel;
use App\Models\WMS\Warehouse\WarehouseForPutAwayModel;
use App\Models\WMS\Warehouse\WarehousePutAwayModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use App\Traits\MOS\ProductionLogTrait;
use App\Traits\WMS\WarehouseLogTrait;
use App\Traits\WMS\WmsCrudOperationsTrait;
use Illuminate\Http\Request;
use Exception;
use DB;

class WarehousePutAwayController extends Controller
{

    use WmsCrudOperationsTrait, ProductionLogTrait, WarehouseLogTrait;
    public function getRules()
    {
        return [
            'created_by_id' => 'required',
            'warehouse_receiving_reference_number' => 'required|exists:wms_warehouse_receiving,reference_number',
            'item_code' => 'required|exists:wms_item_masterdata,item_code',
            'production_items' => 'required',
            'remaining_quantity' => 'required',
        ];
    }

    #region Create Put Away
    public function onCreate(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'warehouse_receiving_reference_number' => 'required|exists:wms_warehouse_receiving,reference_number',
            'item_code' => 'required|exists:wms_item_masterdata,item_code',
            'production_items' => 'required|json',
            'received_quantity' => 'required|integer',
            'scanned_items' => 'required|json',
            'temporary_storage_id' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $warehousePutAwayModel = WarehousePutAwayModel::where('warehouse_receiving_reference_number', $fields['warehouse_receiving_reference_number'])
                ->where('item_code', $fields['item_code'])
                ->where('status', 0)
                ->first();

            if ($warehousePutAwayModel) {
                $this->onUpdatePutAway($fields, $warehousePutAwayModel);
            } else {
                $this->onInitialPutAway($fields);
            }

            DB::commit();
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }

    }

    public function onUpdatePutAway($fields, $warehousePutAwayModel)
    {
        try {
            $productionItems = json_decode($fields['production_items'], true);
            $inclusionArray = ['2.1'];
            $remainingQuantity = null;
            $receivedQuantity = null;
            foreach ($productionItems as &$value) {
                $checkItemScanned = $this->onCheckScannedItems(json_decode($fields['scanned_items'], true), $value['sticker_no'], $value['bid']);
                if ($checkItemScanned && in_array($value['status'], $inclusionArray)) {
                    $productionBatch = ProductionBatchModel::find($value['bid']);
                    $itemMasterdata = $productionBatch->productionOta->itemMasterdata ?? $productionBatch->productionOtb->itemMasterdata;
                    $primaryUom = $itemMasterdata->uom->long_name ?? null;
                    $primaryConversion = $itemMasterdata->primaryConversion->long_name ?? null;
                    $produceItemModel = $productionBatch->productionItems;
                    $producedItems = json_decode($produceItemModel->produced_items, true);

                    $receivedQuantity = json_decode($warehousePutAwayModel->received_quantity, true);
                    $remainingQuantity = json_decode($warehousePutAwayModel->remaining_quantity, true);
                    if ($primaryUom) {
                        if (!isset($remainingQuantity[$primaryUom])) {
                            $remainingQuantity[$primaryUom] = 0;
                        }
                        $remainingQuantity[$primaryUom]++;
                        $receivedQuantity[$primaryUom]++;
                    }

                    if ($primaryConversion) {
                        if (!isset($remainingQuantity[$primaryConversion])) {
                            $remainingQuantity[$primaryConversion] = 0;
                        }
                        $remainingQuantity[$primaryConversion] += intval($producedItems[$value['sticker_no']]['q']);
                        $receivedQuantity[$primaryConversion] += intval($producedItems[$value['sticker_no']]['q']);
                    }
                    $productionItemMerged = json_decode($warehousePutAwayModel->production_items, true);
                    $warehousePutAwayModel->production_items = json_encode($productionItemMerged);
                    $warehousePutAwayModel->received_quantity = json_encode($receivedQuantity);
                    $warehousePutAwayModel->remaining_quantity = json_encode($remainingQuantity);
                    $warehousePutAwayModel->save();
                    $this->createWarehouseLog(null, null, WarehouseReceivingModel::class, $warehousePutAwayModel->id, $warehousePutAwayModel->getAttributes(), $fields['created_by_id'], 1);

                    $producedItems[$value['sticker_no']]['status'] = 3; // Received
                    $produceItemModel->produced_items = json_encode($producedItems);
                    $produceItemModel->save();
                    $this->createProductionLog(ProductionItemModel::class, $produceItemModel->id, $producedItems[$value['sticker_no']], $fields['created_by_id'], 1, $value['sticker_no']);

                    $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $warehousePutAwayModel->warehouse_receiving_reference_number)
                        ->where('production_order_id', $productionBatch->production_order_id)
                        ->where('batch_number', $productionBatch->batch_number)
                        ->where('item_code', $fields['item_code'])
                        ->first();
                    $warehouseReceivingProductionItems = json_decode($warehouseReceiving->produced_items, true);
                    $warehouseReceivingProductionItems[$value['sticker_no']]['status'] = 3; // Received
                    $warehouseReceiving->produced_items = json_encode($warehouseReceivingProductionItems);
                    $warehouseReceiving->save();
                    $this->createWarehouseLog(ProductionItemModel::class, $produceItemModel->id, WarehouseReceivingModel::class, $warehouseReceiving->id, $warehouseReceiving->getAttributes(), $fields['created_by_id'], 1);

                }
                unset($value);
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
    public function onInitialPutAway($fields)
    {
        try {
            $warehouseReceivingReferenceNumber = $fields['warehouse_receiving_reference_number'];
            $referenceNumber = WarehousePutAwayModel::onGenerateWarehousePutAwayReferenceNumber($warehouseReceivingReferenceNumber);
            $productionItems = json_decode($fields['production_items'], true);
            $remainingQuantity = [];
            foreach ($productionItems as &$value) {
                $flag = $this->onCheckScannedItems(json_decode($fields['scanned_items'], true), $value['sticker_no'], $value['bid']);
                if ($flag) {
                    $productionBatch = ProductionBatchModel::find($value['bid']);
                    $itemMasterdata = $productionBatch->productionOta->itemMasterdata ?? $productionBatch->productionOtb->itemMasterdata;
                    $primaryUom = $itemMasterdata->uom->long_name ?? null;
                    $primaryConversion = $itemMasterdata->primaryConversion->long_name ?? null;
                    $produceItemModel = $productionBatch->productionItems;
                    $producedItems = json_decode($produceItemModel->produced_items, true);
                    if ($primaryUom) {
                        if (!isset($remainingQuantity[$primaryUom])) {
                            $remainingQuantity[$primaryUom] = 0;
                        }
                        $remainingQuantity[$primaryUom]++;
                    }

                    if ($primaryConversion) {
                        if (!isset($remainingQuantity[$primaryConversion])) {
                            $remainingQuantity[$primaryConversion] = 0;
                        }
                        $remainingQuantity[$primaryConversion] += intval($producedItems[$value['sticker_no']]['q']);
                    }

                    $value['status'] = 3;
                    $producedItems[$value['sticker_no']]['status'] = 3; // Received
                    $produceItemModel->produced_items = json_encode($producedItems);
                    $produceItemModel->save();
                    $this->createProductionLog(ProductionItemModel::class, $produceItemModel->id, $producedItems[$value['sticker_no']], $fields['created_by_id'], 1, $value['sticker_no']);

                    $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $warehouseReceivingReferenceNumber)
                        ->where('production_order_id', $productionBatch->production_order_id)
                        ->where('batch_number', $productionBatch->batch_number)
                        ->where('item_code', $fields['item_code'])
                        ->first();
                    $warehouseReceivingProductionItems = json_decode($warehouseReceiving->produced_items, true);
                    $warehouseReceivingProductionItems[$value['sticker_no']]['status'] = 3; // Received
                    $warehouseReceiving->produced_items = json_encode($warehouseReceivingProductionItems);
                    $warehouseReceiving->save();
                    $this->createWarehouseLog(ProductionItemModel::class, $produceItemModel->id, WarehouseReceivingModel::class, $warehouseReceiving->id, $warehouseReceiving->getAttributes(), $fields['created_by_id'], 1);

                }
                unset($value);
            }
            $warehousePutAway = new WarehousePutAwayModel();
            $warehousePutAway->created_by_id = $fields['created_by_id'];
            $warehousePutAway->reference_number = $referenceNumber;
            $warehousePutAway->warehouse_receiving_reference_number = $warehouseReceivingReferenceNumber;
            $warehousePutAway->item_code = $fields['item_code'];
            $warehousePutAway->production_items = json_encode($productionItems);
            $warehousePutAway->received_quantity = json_encode($remainingQuantity);
            $warehousePutAway->remaining_quantity = json_encode($remainingQuantity);
            $warehousePutAway->temporary_storage_id = $fields['temporary_storage_id'];
            $warehousePutAway->save();
            $this->createWarehouseLog(null, null, WarehousePutAwayModel::class, $warehousePutAway->id, $warehousePutAway->getAttributes(), $fields['created_by_id'], 0);

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }
    #endregion

    #region Check Item Status
    public function onItemCheckHoldInactiveDone($producedItems, $itemKey, $inclusionArray, $exclusionArray)
    {
        $inArrayFlag = count($inclusionArray) > 0 ?
            in_array($producedItems[$itemKey]['status'], $inclusionArray) :
            !in_array($producedItems[$itemKey]['status'], $exclusionArray);
        return $producedItems[$itemKey]['sticker_status'] != 0 && $inArrayFlag;
    }

    public function onCheckScannedItems($scannedItems, $stickerNumber, $batchId)
    {
        try {
            foreach ($scannedItems as $value) {
                if (($value['sticker_no'] == $stickerNumber) && $value['bid'] == $batchId) {
                    return true;
                }
            }

            return false;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());

        }
    }
    #endregion

    #region Getters
    public function onGetById($id)
    {
        try {
            return $this->readRecordById(WarehousePutAwayModel::class, $id, 'Warehouse Put Away', 'itemMasterdata');
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onGetCurrent($status)
    {
        $whereFields = [
            'status' => $status
        ];
        $orderFields = [
            'id' => 'ASC'
        ];
        return $this->readCurrentRecord(WarehousePutAwayModel::class, null, $whereFields, 'itemMasterdata', $orderFields, 'Warehouse Put Away');
    }
    #endregion

    #region Sub-Standard Items
    public function onSubStandard(Request $request, $referenceNumber)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'scanned_items' => 'required',
            'reason' => 'required',
            'attachment' => 'nullable',
        ]);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $scannedItems = json_decode($fields['scanned_items'], true);
            $reason = $fields['reason'];
            $attachment = $fields['attachment'] ?? null;
            $locationId = 4; // Warehouse Transfer
            foreach ($scannedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionItem = $productionBatch->productionItems;
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $inclusionArray = ['2'];
                $itemMasterdata = $productionOrderToMake->itemMasterdata;
                $primaryUom = $itemMasterdata->uom->long_name ?? null;
                $primaryConversion = $itemMasterdata->primaryConversion->long_name ?? null;
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                if (true) {
                    $warehousePutAway = WarehousePutAwayModel::where('reference_number', $referenceNumber)
                        ->where('item_code', $itemCode)
                        ->first();
                    if ($warehousePutAway) {
                        $warehousePutAwayProducedItems = json_decode($warehousePutAway->production_items, true);
                        $warehousePutAwayProducedItems[$itemDetails['sticker_no']]['status'] = 1.1;
                        $warehousePutAway->production_items = json_encode($warehousePutAwayProducedItems);
                        $substandardQuantity = json_decode($warehousePutAway->substandard_quantity, true);
                        $remainingQuantity = json_decode($warehousePutAway->remaining_quantity, true);

                        if ($primaryUom) {
                            if (!isset($remainingQuantity[$primaryUom])) {
                                $remainingQuantity[$primaryUom] = 0;
                            }
                            if (!isset($substandardQuantity[$primaryUom])) {
                                $substandardQuantity[$primaryUom] = 0;
                            }
                            $remainingQuantity[$primaryUom]--;
                            $substandardQuantity[$primaryUom]++;
                        }

                        if ($primaryConversion) {
                            if (!isset($remainingQuantity[$primaryConversion])) {
                                $remainingQuantity[$primaryConversion] = 0;
                            }
                            if (!isset($substandardQuantity[$primaryConversion])) {
                                $substandardQuantity[$primaryConversion] = 0;
                            }
                            $remainingQuantity[$primaryConversion] -= intval($itemDetails['q']);
                            $substandardQuantity[$primaryConversion] += intval($itemDetails['q']);
                        }
                        $warehousePutAway->remaining_quantity = json_encode($remainingQuantity);
                        $warehousePutAway->substandard_quantity = json_encode($substandardQuantity);
                        $warehousePutAway->save();
                    }
                }
            }

            $substandardController = new SubStandardItemController();
            $substandardRequest = new Request([
                'created_by_id' => $createdById,
                'scanned_items' => $fields['scanned_items'],
                'reason' => $reason,
                'attachment' => $attachment,
                'location_id' => $locationId,
            ]);

            $substandardController->onCreate($substandardRequest);
            DB::commit();
            return $this->dataResponse('success', 201, 'Sub-Standard ' . __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Sub-Standard ' . __('msg.create_failed'));
        }
    }

    public function onCompleteTransaction(Request $request, $referenceNumber)
    {
        $fields = $request->validate([
            'created_by_id' => 'required'
        ]);
        try {
            $createdById = $fields['created_by_id'];
            $warehousePutAway = WarehousePutAwayModel::where('reference_number', $referenceNumber)
                ->where('status', 0)
                ->firstOrFail();

            DB::beginTransaction();
            $warehouseForPutAway = WarehouseForPutAwayModel::where('warehouse_put_away_id', $warehousePutAway->id)->first();

            $warehousePutAwayItem = json_decode($warehousePutAway->production_items, true);
            $subLocationId = null;
            $subLocationLayer = null;
            $discrepancyArr = [];
            foreach ($warehousePutAwayItem as $value) {
                $productionItemModel = ProductionItemModel::where('production_batch_id', $value['bid'])->first();
                $productionItem = json_decode($productionItemModel->produced_items, true)[$value['sticker_no']];
                $subLocationId = $productionItem['sub_location']['sub_location_id'];
                $subLocationLayer = $productionItem['sub_location']['layer_level'];

                if ($productionItem['status'] != 13) {
                    $discrepancyArr[] = $value;
                }
            }
            if (count($discrepancyArr) > 0) {
                $warehousePutAway->discrepancy_data = json_encode($discrepancyArr);
            }
            $queuedPermanentStorage = QueuedSubLocationModel::where([
                'sub_location_id' => $subLocationId,
                'layer_level' => $subLocationLayer,
                'status' => 1
            ])->first();
            if ($queuedPermanentStorage) {
                $queuedPermanentStorage->status = 0;
                $queuedPermanentStorage->save();
            }

            if ($warehousePutAway) {
                $temporaryStorageId = $warehousePutAway->temporary_storage_id;
                $warehousePutAway->status = 1;
                $warehousePutAway->temporary_storage_id = null;

                $warehousePutAway->save();
                $this->createWarehouseLog(null, null, WarehousePutAwayModel::class, $warehousePutAway->id, $warehousePutAway->getAttributes(), $createdById, 0);

                $queuedTemporaryStorage = QueuedTemporaryStorageModel::where('sub_location_id', $temporaryStorageId)->orderBy('id', 'DESC')->first();
                if ($queuedTemporaryStorage) {
                    $queuedTemporaryStorage->delete();
                }
            }
            DB::commit();
            return $this->dataResponse('success', 200, 'Warehouse Put Away ' . __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Warehouse Put Away ' . __('msg.update_failed'), $exception->getMessage());

        }
    }

    public function onCheckItemReceive($receiveItemsArr, $key, $value, $ReferenceItemCode)
    {
        try {
            foreach ($receiveItemsArr as $receiveValue) {
                if (($receiveValue['bid'] == $value['bid']) && ($receiveValue['sticker_no'] == $key) && ($receiveValue['item_code'] == $ReferenceItemCode)) {
                    return true;
                }
            }
            return false;
        } catch (Exception $exception) {

            return false;
        }
    }
}

