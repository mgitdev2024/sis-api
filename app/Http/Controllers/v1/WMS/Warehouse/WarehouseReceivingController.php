<?php

namespace App\Http\Controllers\v1\WMS\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\QualityAssurance\SubStandardItemController;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\QualityAssurance\SubStandardItemModel;
use App\Models\Settings\WarehouseLocationModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Warehouse\WarehouseForReceiveModel;
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
                DB::raw('SUM(substandard_quantity) as substandard_quantity'),
                DB::raw('SUM(received_quantity) as received_quantity'),
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
                    'received_quantity' => $value->received_quantity,
                    'substandard_quantity' => $value->substandard_quantity,
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
    public function onGetCurrent($referenceNumber, $status, $received_status = null)
    {
        try {
            $warehouseReceivingAdd = WarehouseReceivingModel::select(
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
                    'reference_number'
                ]);
            if ($received_status == 1) {
                $warehouseReceivingAdd->havingRaw('SUM(received_quantity) + SUM(substandard_quantity) <> SUM(JSON_LENGTH(produced_items))');
            }

            $warehouseReceiving = $warehouseReceivingAdd->get();

            $warehouseReceivingArr = [];
            foreach ($warehouseReceiving as $value) {
                $itemCode = $value->item_code;
                $warehouseReceivingArr[] = [
                    'reference_number' => $value->reference_number,
                    'quantity' => $value->produced_items_count,
                    'received_quantity' => $value->received_quantity,
                    'substandard_quantity' => $value->substandard_quantity,
                    'item_code' => $itemCode,
                    'sku_type' => ItemMasterdataModel::where('item_code', $itemCode)->first()->item_category_label
                ];
            }
            if (count($warehouseReceivingArr) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $warehouseReceivingArr);
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
    public function onUpdate(Request $request, $referenceNumber)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'scanned_items' => 'nullable|string', // {slid:1}
            'action' => 'required|string|in:0,1', // 0 = Scan, 1 = Complete Transaction
        ]);
        try {
            if ($fields['action'] == 0) {
                $scannedItems = json_decode($fields['scanned_items'], true);
                $this->onScanItems($scannedItems, $referenceNumber, $fields['created_by_id']);
            } else {
                $this->onCompleteTransaction($referenceNumber, $fields['created_by_id']);
            }

            return $this->dataResponse('success', 200, __('msg.record_found'));

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Warehouse Receiving ' . $exception->getMessage());
        }
    }

    public function onScanItems($scannedItems, $referenceNumber, $createdById)
    {
        try {
            DB::beginTransaction();
            foreach ($scannedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionItem = $productionBatch->productionItems;
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $inclusionArray = [2];
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                if ($flag) {
                    $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $referenceNumber)
                        ->where('production_order_id', $productionBatch->production_order_id)
                        ->where('batch_number', $productionBatch->batch_number)
                        ->where('item_code', $itemCode)
                        ->first();
                    if ($warehouseReceiving) {
                        $warehouseForReceive = WarehouseForReceiveModel::where('reference_number', $referenceNumber)->delete();
                        $warehouseReceiving->received_quantity = ++$warehouseReceiving->received_quantity;
                        $warehouseReceiving->updated_by_id = $createdById;
                        $warehouseReceiving->save();
                        $this->createWarehouseLog(ProductionItemModel::class, $productionItem->id, WarehouseReceivingModel::class, $warehouseReceiving->id, $warehouseReceiving->getAttributes(), $createdById, 0);
                    }
                }
            }
            DB::commit();

        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }

    public function onCompleteTransaction($referenceNumber, $createdById)
    {
        try {
            $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $referenceNumber)
                ->where('status', 0)
                ->get();

            if (count($warehouseReceiving) <= 0) {
                throw new Exception('Warehouse Receiving reference number already received');
            }
            DB::beginTransaction();
            foreach ($warehouseReceiving as $warehouseReceivingValue) {
                $warehouseReceiveItems = json_decode($warehouseReceivingValue->produced_items, true);
                $productionItems = $warehouseReceivingValue->productionBatch->productionItems;
                $producedItems = json_decode($productionItems->produced_items, true);
                foreach ($warehouseReceiveItems as $key => &$items) {
                    $producedItems[$key]['status'] = 3;
                    $items['status'] = 3;

                    $this->createProductionLog(ProductionItemModel::class, $productionItems->id, $producedItems[$key], $createdById, 1, $key);
                    unset($items);
                }
                $warehouseReceivingValue->produced_items = json_encode($warehouseReceiveItems);
                $warehouseReceivingValue->status = 1;
                $warehouseReceivingValue->updated_by_id = $createdById;
                $warehouseReceivingValue->save();

                $productionItems->produced_items = json_encode($producedItems);
                $productionItems->save();
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
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
            $locationId = 3; // Warehouse Receiving
            foreach ($scannedItems as $itemDetails) {
                $productionBatch = ProductionBatchModel::find($itemDetails['bid']);
                $productionItem = $productionBatch->productionItems;
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $inclusionArray = [2];
                $flag = $this->onItemCheckHoldInactiveDone(json_decode($productionItem->produced_items, true), $itemDetails['sticker_no'], $inclusionArray, []);
                if ($flag) {
                    $decodeItems = json_decode($productionItem->produced_items, true);
                    $decodeItems[$itemDetails['sticker_no']]['status'] = 1.1;
                    $productionItem->produced_items = json_encode($decodeItems);
                    $productionItem->save();

                    $warehouseReceiving = WarehouseReceivingModel::where('reference_number', $referenceNumber)
                        ->where('production_order_id', $productionBatch->production_order_id)
                        ->where('batch_number', $productionBatch->batch_number)
                        ->where('item_code', $itemCode)
                        ->first();
                    if ($warehouseReceiving) {
                        $warehouseReceiving->substandard_quantity = ++$warehouseReceiving->substandard_quantity;
                        $warehouseReceiving->save();
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
}
