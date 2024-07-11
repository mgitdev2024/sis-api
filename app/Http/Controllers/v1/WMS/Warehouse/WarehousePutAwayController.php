<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
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
            'scanned_items' => 'required|json'
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

    #region Update Warehouse Put Away
    public function onUpdate(Request $request, $warehousePutAwayId)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'scanned_items' => 'nullable|string', // {slid:1}
            'action' => 'required|string|in:0,1', // 0 = Scan, 1 = Complete Transaction
        ]);
        try {
            DB::beginTransaction();
            if ($fields['action'] == 0) {
                $scannedItems = json_decode($fields['scanned_items'], true);
                $this->onScanItems($scannedItems, $warehousePutAwayId, $fields['created_by_id']);
                $this->onCreatePutAway($scannedItems, $warehousePutAwayId, $fields['created_by_id']);

            } else {
                $this->onCompleteTransaction($warehousePutAwayId, $fields['created_by_id']);
            }

            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Warehouse Receiving ' . $exception->getMessage());
        }
    }

    public function onScanItems($scannedItems, $warehousePutAwayId, $createdById)
    {
        try {
            DB::beginTransaction();
            foreach ($scannedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionItem = $productionBatch->productionItems;
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $inclusionArray = [3];
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                if ($flag) {
                    $producedItems = json_decode($productionItem->produced_items, true);
                    $producedItems[$itemDetails['sticker_no']]['status'] = '2.1';
                    $productionItem->produced_items = json_encode($producedItems);
                    $productionItem->save();
                    $this->createProductionLog(ProductionItemModel::class, $productionItem->id, $producedItems[$itemDetails['sticker_no']], $createdById, 1, $itemDetails['sticker_no']);

                    $warehouseReceiving = WarehousePutAwayModel::find($warehousePutAwayId);

                    if ($warehouseReceiving) {
                        $warehouseForReceive = WarehouseForPutAwayModel::where('warehouse_put_away_id', $warehousePutAwayId)->update(['status' => 0]);
                        $warehouseProducedItems = json_decode($warehouseReceiving->produced_items, true);
                        $warehouseProducedItems[$itemDetails['sticker_no']]['status'] = '2.1';
                        $warehouseReceiving->produced_items = json_encode($warehouseProducedItems);
                        $warehouseReceiving->received_quantity = ++$warehouseReceiving->received_quantity;
                        $warehouseReceiving->updated_by_id = $createdById;
                        $warehouseReceiving->save();
                        $this->createWarehouseLog(ProductionItemModel::class, $productionItem->id, WarehouseReceivingModel::class, $warehouseReceiving->id, $warehouseReceiving->getAttributes(), $createdById, 1);
                    }
                }
            }
            DB::commit();

        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }

    #endregion
}

