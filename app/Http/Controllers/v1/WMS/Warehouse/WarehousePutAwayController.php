<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
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
            $receivedQuantity = 0;
            $productionItems = json_decode($fields['production_items'], true);
            $inclusionArray = ['2.1'];
            $remainingQuantity = null;
            foreach ($productionItems as $value) {
                $checkItemScanned = $this->onCheckScannedItems(json_decode($fields['scanned_items'], true), $value['sticker_no'], $value['bid']);
                if ($checkItemScanned && in_array($value['status'], $inclusionArray)) {
                    ++$receivedQuantity;

                    $productionBatch = ProductionBatchModel::find($value['bid']);
                    $itemMasterdata = $productionBatch->productionOta->itemMasterdata ?? $productionBatch->productionOtb->itemMasterdata;
                    $primaryUom = $itemMasterdata->uom->long_name ?? null;
                    $primaryConversion = $itemMasterdata->primaryConversion->long_name ?? null;
                    $produceItemModel = $productionBatch->productionItems;
                    $producedItems = json_decode($produceItemModel->produced_items, true);

                    $warehousePutAwayModel->received_quantity += 1;
                    $remainingQuantity = json_decode($warehousePutAwayModel->remaining_quantity, true);
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

            foreach ($productionItems as $value) {
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

            }
            $warehousePutAway = new WarehousePutAwayModel();
            $warehousePutAway->created_by_id = $fields['created_by_id'];
            $warehousePutAway->reference_number = $referenceNumber;
            $warehousePutAway->warehouse_receiving_reference_number = $warehouseReceivingReferenceNumber;
            $warehousePutAway->item_code = $fields['item_code'];
            $warehousePutAway->production_items = $fields['production_items'];
            $warehousePutAway->received_quantity = $fields['received_quantity'];
            $warehousePutAway->remaining_quantity = json_encode($remainingQuantity);
            $warehousePutAway->save();
            $this->createWarehouseLog(null, null, WarehousePutAwayModel::class, $warehousePutAway->id, $warehousePutAway->getAttributes(), $fields['created_by_id'], 0);

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

    }

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

    #region Getters
    public function onGetById($id)
    {
        try {
            return $this->readRecordById(WarehousePutAwayModel::class, $id, 'Warehouse Put Away', 'itemMasterdata');
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
    #endregion
}
