<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\Settings\WarehouseLocationModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use DB;
use Exception;
use App\Traits\MOS\MosCrudOperationsTrait;

class WarehouseReceivingController extends Controller
{
    use MosCrudOperationsTrait, QueueSubLocationTrait;
    public function onGetAllCategory($status)
    {
        try {
            $itemDisposition = WarehouseReceivingModel::select(
                'reference_number',
                DB::raw('count(*) as batch_count'),
                DB::raw('SUM(JSON_LENGTH(produced_items))  as produced_items_count')
            )
                ->where('status', $status)
                ->groupBy([
                    'reference_number',
                ])
                ->get();
            $warehouseReceiving = [];
            $counter = 0;
            foreach ($itemDisposition as $value) {
                $warehouseReceiving[$counter] = [
                    'reference_number' => $value->reference_number,
                    'batch_count' => $value->batch_count,
                    'quantity' => $value->produced_items_count,
                ];
                ++$counter;
            }
            if (count($warehouseReceiving) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $warehouseReceiving);
            }
            return $this->dataResponse('error', 200, 'Warehouse Receiving ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    #region Separate batches per item code
    // public function onGetCurrent($referenceNumber, $status)
    // {
    //     $whereFields = [
    //         'reference_number' => $referenceNumber,
    //         'status' => $status // 0, 1
    //     ];

    //     $orderFields = [
    //         'reference_number' => 'ASC'
    //     ];
    //     return $this->readCurrentRecord(WarehouseReceivingModel::class, null, $whereFields, null, $orderFields, null, 'Warehouse Receiving');
    // }
    #endregion
    public function onGetCurrent($referenceNumber, $status)
    {
        try {
            $itemDisposition = WarehouseReceivingModel::select(
                'reference_number',
                'item_code',
                DB::raw('SUM(substandard_quantity) as substandard_quantity'),
                DB::raw('SUM(received_quantity) as received_quantity'),
                DB::raw('SUM(JSON_LENGTH(produced_items)) as produced_items_count')
            )
                ->where('status', $status)
                ->where('reference_number', $referenceNumber)
                ->groupBy([
                    'item_code',
                    'reference_number',
                    'received_quantity',
                    'substandard_quantity',
                ])
                ->get();
            $warehouseReceiving = [];
            $counter = 0;
            foreach ($itemDisposition as $value) {
                $warehouseReceiving[$counter] = [
                    'reference_number' => $value->reference_number,
                    'quantity' => $value->produced_items_count,
                    'received_quantity' => $value->received_quantity,
                    'substandard_quantity' => $value->substandard_quantity,
                    'item_code' => $value->item_code,
                    'sku_type' => ItemMasterdataModel::where('item_code', $value->item_code)->first()->item_category_label
                ];
                ++$counter;
            }
            if (count($warehouseReceiving) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $warehouseReceiving);
            }
            return $this->dataResponse('error', 200, 'Warehouse Receiving ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetById($id)
    {
        return $this->readRecordById(WarehouseReceivingModel::class, $id, 'Warehouse Receiving');
    }
    public function onUpdate(Request $request)
    {
        $fields = $request->validate([
            'scanned_items' => 'required|string', // {slid:1}
            'reference_number' => 'required|string'
        ]);
        try {
            $scannedItems = null;
            $referenceNumber = $fields['reference_number'];
            $dataEncodedItems = json_decode($fields['scanned_items'], true);
            if (isset($dataEncodedItems['slid'])) {
                $scannedItems = $this->onGetQueuedItems($dataEncodedItems['slid'], false);
                $this->onScanTemporaryStorage($scannedItems, $referenceNumber);
            } else {
                $scannedItems = json_decode($fields['scanned_items'], true);
                $this->onScanItems($scannedItems, $referenceNumber);
            }
            return $this->dataResponse('success', 200, __('msg.record_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Warehouse Receiving ' . $exception->getMessage());
        }
    }

    public function onScanTemporaryStorage($layers, $referenceNumber)
    {
        try {
            DB::beginTransaction();
            $receivedQuantity = 0;
            foreach ($layers as $layerValue) {
                foreach ($layerValue as $itemDetails) {
                    $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                    $productionItem = $productionBatch->productionItems;
                    $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                    $itemCode = $productionOrderToMake->item_code;
                    $inclusionArray = [2];
                    $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                    if ($flag) {
                        $decodeItems = json_decode($productionItem->produced_items, true);
                        $decodeItems[$itemDetails['sticker_no']]['status'] = 3;
                        $productionItem->produced_items = json_encode($decodeItems);
                        $productionItem->save();
                        $receivedQuantity++;

                        $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $referenceNumber)
                            ->where('production_order_id', $productionBatch->production_order_id)
                            ->where('batch_number', $productionBatch->batch_number)
                            ->where('item_code', $itemCode)
                            ->first();
                        if ($warehouseReceiving) {
                            $warehouseReceiving->received_quantity = ++$warehouseReceiving->received_quantity;
                            $warehouseReceiving->status = 1;
                            $warehouseReceiving->save();
                        }
                    }
                }
            }

            DB::commit();

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Warehouse Receiving ' . $exception->getMessage());

        }
    }

    public function onScanItems($scannedItems, $referenceNumber)
    {
        try {
            DB::beginTransaction();
            $receivedQuantity = 0;
            foreach ($scannedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionItem = $productionBatch->productionItems;
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $inclusionArray = [2];
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                if ($flag) {
                    $decodeItems = json_decode($productionItem->produced_items, true);
                    $decodeItems[$itemDetails['sticker_no']]['status'] = 3;
                    $productionItem->produced_items = json_encode($decodeItems);
                    $productionItem->save();
                    $receivedQuantity++;

                    $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $referenceNumber)
                        ->where('production_order_id', $productionBatch->production_order_id)
                        ->where('batch_number', $productionBatch->batch_number)
                        ->where('item_code', $itemCode)
                        ->first();
                    if ($warehouseReceiving) {
                        $warehouseReceiving->received_quantity = ++$warehouseReceiving->received_quantity;
                        $warehouseReceiving->status = 1;
                        $warehouseReceiving->save();
                    }
                }
            }
            DB::commit();

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Warehouse Receiving ' . $exception->getMessage());
        }
    }

    public function onItemCheckHoldInactiveDone($producedItems, $itemKey, $inclusionArray, $exclusionArray)
    {
        $inArrayFlag = count($inclusionArray) > 0 ?
            in_array($producedItems[$itemKey]['status'], $inclusionArray) :
            !in_array($producedItems[$itemKey]['status'], $exclusionArray);
        return $producedItems[$itemKey]['sticker_status'] != 0 && $inArrayFlag;
    }
}
