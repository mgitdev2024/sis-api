<?php

namespace App\Http\Controllers\v1\MOS\Production;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use App\Models\QualityAssurance\SubStandardItemModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use App\Traits\WMS\WarehouseLogTrait;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use App\Traits\MOS\MosCrudOperationsTrait;

use Exception;
use DB;

class ProductionItemController extends Controller
{
    use MosCrudOperationsTrait, WarehouseLogTrait, QueueSubLocationTrait;
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'updated_by_id' => 'required',
            'chilled_exp_date' => 'required|date',
        ];
        return $this->updateRecordById(ProductionItemModel::class, $request, $rules, 'Produced Item', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionItemModel::class, $request, $searchableFields, 'Produced Item');
    }
    public function onGetAll()
    {
        return $this->readRecord(ProductionItemModel::class, 'Produced Item');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionItemModel::class, $id, 'Produced Item');
    }

    public function onChangeStatus(Request $request)
    {
        #region status list
        // 0 => 'Good',
        // 1 => 'On Hold',
        // 1.1 => 'On Hold - Sub Standard
        // 2 => 'For Receive',
        // 2.1 => 'For Receive - Inbound',
        // 3 => 'Received',
        // 3.1 => 'For Put-away - In Process',
        // 4 => 'For Investigation',
        // 5 => 'For Sampling',
        // 6 => 'For Retouch',
        // 7 => 'For Slice',
        // 8 => 'For Sticker Update',
        // 9 => 'Sticker Updated',
        // 10 => 'Reviewed',
        // 11 => 'Retouched',
        // 12 => 'Sliced',
        // 13 => 'Stored',
        #endregion

        $rules = [
            'scanned_item_qr' => 'required|string',
            'status_id' => 'nullable|between:0,5|required_without_all:is_deactivate',
            'is_deactivate' => 'nullable|in:1|required_without_all:status_id',
            'production_batch_id' => 'nullable|required_if:is_deactivate,1',
            'temporary_storage_id' => 'nullable|exists:wms_storage_sub_locations,id',
            'created_by_id' => 'required'
        ];
        $fields = $request->validate($rules);
        $statusId = $fields['status_id'] ?? 0;
        $createdBy = $fields['created_by_id'];
        return isset($fields['is_deactivate']) ? $this->onDeactivateItem($fields) : $this->onUpdateItemStatus($statusId, $fields, $createdBy);
    }

    public function onUpdateItemStatus($statusId, $fields, $createdById)
    {
        try {
            DB::beginTransaction();
            $forQaDisposition = [4, 5];
            $scannedItem = json_decode($fields['scanned_item_qr'], true);
            $temporaryStorageId = $fields['temporary_storage_id'] ?? null;
            // For Warehouse Receiving
            if ($statusId == 2) {
                $this->onWarehouseReceiveItem($scannedItem, $createdById, $temporaryStorageId);
            }
            foreach ($scannedItem as $value) {
                $productionBatch = ProductionBatchModel::find($value['bid']);
                $itemCode = $productionBatch->productionOta->item_code ?? $productionBatch->productionOtb->item_code;
                $producedItems = json_decode($productionBatch->productionItems->produced_items, true);
                $productionType = $productionBatch->productionItems->production_type;
                if ($statusId == 2) {
                    $this->onForReceiveItem($value['bid'], $producedItems[$value['sticker_no']], $value['sticker_no'], $createdById);
                } else if (in_array($statusId, $forQaDisposition)) {
                    $this->onItemDisposition($createdById, $value['bid'], $producedItems[$value['sticker_no']], $itemCode, $value['sticker_no'], $statusId, $productionType);
                } else {
                    $this->onUpdateOtherStatus($productionBatch, $statusId, $value['sticker_no'], $createdById);
                }
            }

            DB::commit();
            return $this->dataResponse('success', 201, 'Produced Item ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Produced Item ' . $exception->getMessage());
        }
    }

    public function onDeactivateItem($fields)
    {
        try {
            DB::beginTransaction();

            $scannedItem = json_decode($fields['scanned_item_qr'], true);

            $productionBatch = ProductionBatchModel::find($fields['production_batch_id']);
            $producedItemModel = $productionBatch->productionItems;
            $productionItems = $producedItemModel->produced_items;
            $producedItemArray = json_decode($productionItems, true);

            foreach ($scannedItem as $value) {
                $producedItemArray[$value['sticker_no']]['sticker_status'] = 0;
                $producedItemArray[$value['sticker_no']]['status'] = null;
                $this->createProductionLog(ProductionItemModel::class, $producedItemModel->id, $value, $fields['created_by_id'], 1, $value['sticker_no']);
            }

            $producedItemModel->produced_items = json_encode($producedItemArray);
            $producedItemModel->save();

            DB::commit();
            return $this->dataResponse('success', 201, 'Produced Item ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Produced Item ' . __('msg.update_failed'));
        }
    }

    public function onItemDisposition($createdById, $id, $value, $itemCode, $itemKey, $statusId, $productionType)
    {
        try {
            $type = 1;
            if ($statusId == 4) {
                $type = 0;
            }
            $exclusionArray = [1, 4, 5, 6, 7, 8];
            $producedItemModel = ProductionItemModel::where('production_batch_id', $id)->first();
            $producedItems = json_decode($producedItemModel->produced_items, true);
            $flag = $this->onItemCheckHoldInactiveDone($producedItems, $itemKey, [], $exclusionArray);

            if ($flag) {
                $itemDisposition = new ItemDispositionModel();
                $itemDisposition->created_by_id = $createdById;
                $itemDisposition->production_batch_id = $id;
                $itemDisposition->item_code = $itemCode;
                $itemDisposition->item_key = $itemKey;
                $itemDisposition->type = $type;
                $itemDisposition->production_type = $productionType;
                $itemDisposition->produced_items = json_encode([$itemKey => $value]);
                $itemDisposition->save();
                $this->createProductionLog(ItemDispositionModel::class, $itemDisposition->id, $itemDisposition->getAttributes(), $createdById, 1, $itemKey);

                $producedItems[$itemKey]['status'] = $statusId;
                $producedItemModel->produced_items = json_encode($producedItems);
                $producedItemModel->save();
                $this->createProductionLog(ProductionItemModel::class, $producedItemModel->id, $producedItems[$itemKey], $createdById, 1, $itemKey);

                $subStandardItem = SubStandardItemModel::where('production_batch_id', $id)
                    ->where('item_key', $itemKey)
                    ->where('status', 1)
                    ->first();
                if ($subStandardItem) {
                    $subStandardItem->status = 0;
                    $subStandardItem->save();
                    $this->createProductionLog(SubStandardItemModel::class, $subStandardItem->id, $subStandardItem, $createdById, 1, $itemKey);

                }
                return $itemDisposition;
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onForReceiveItem($id, $value, $itemKey, $createdById)
    {
        try {
            $producedItemModel = ProductionItemModel::where('production_batch_id', $id)->first();
            $producedItems = json_decode($producedItemModel->produced_items, true);
            $inclusionArray = [0, 9];
            $flag = $this->onItemCheckHoldInactiveDone($producedItems, $itemKey, $inclusionArray, []);
            if ($flag) {
                $productionBatch = ProductionBatchModel::find($id);
                $productionBatch->actual_quantity += 1;
                $productionBatch->actual_secondary_quantity += intval($value['q']);
                $productionBatch->save();

                $productionActualQuantity = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $productionActualQuantity->actual_quantity += 1;
                $productionActualQuantity->actual_secondary_quantity += intval($value['q']);
                $productionActualQuantity->save();

                $producedItems[$itemKey]['status'] = 2;
                $producedItemModel->produced_items = json_encode($producedItems);
                $producedItemModel->save();
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onUpdateOtherStatus($productionBatch, $statusId, $itemKey, $createdById)
    {
        try {
            $producedItemModel = $productionBatch->productionItems;
            $producedItems = json_decode($producedItemModel->produced_items, true);
            if ($producedItems[$itemKey]['sticker_status'] != 0) {
                $producedItems[$itemKey]['status'] = $statusId;
                $producedItemModel->produced_items = json_encode($producedItems);
                $producedItemModel->save();
                $this->createProductionLog(ProductionItemModel::class, $producedItemModel->id, $producedItems[$itemKey], $createdById, 1, $itemKey);
            }
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

    public function onCheckItemStatus($batch_id, $item_key, $item_quantity)
    {
        try {
            $productionBatch = ProductionBatchModel::find($batch_id);
            $productionItemsModel = $productionBatch->productionItems;
            if ($productionItemsModel) {
                $productionOrderToMake = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                $itemCode = $productionOrderToMake->item_code;
                $item = json_decode($productionItemsModel->produced_items, true)[$item_key];

                $stickerStatus = $item['sticker_status'];
                $itemStatus = $item['status'];
                if ($item['status'] == 9) {
                    if ($item_quantity != $item['q']) {
                        $stickerStatus = 0;
                    }
                }
                $itemMasterdata = ItemMasterdataModel::where('item_code', $itemCode)->first();
                $warehouseReceivingRefNo = $item['warehouse']['warehouse_receiving']['reference_number'] ?? null;
                $subLocationArr = $item['sub_location'] ?? null;

                $data = [
                    'item_code' => $itemCode,
                    'item_status' => $itemStatus,
                    'sticker_status' => $stickerStatus,
                    'production_order_status' => $productionBatch->productionOrder->status,
                    'production_type' => $productionItemsModel->production_type, // 0 = otb, = 1 ota
                    'endorsed_by_qa' => $item['endorsed_by_qa'] ?? 0,
                    'is_viewable_by_otb' => $itemMasterdata->is_viewable_by_otb,
                    'item_sticker_code' => $productionBatch->batch_code . '-' . str_pad($item_key, 3, '0', STR_PAD_LEFT),
                    'item_details' => $productionBatch,
                ];

                if ($warehouseReceivingRefNo) {
                    $data['warehouse'] = $item['warehouse'];
                }

                if ($subLocationArr) {
                    $data['sub_location'] = $subLocationArr;
                }

                return $this->dataResponse('success', 200, 'Produced Item ' . __('msg.record_found'), $data);
            }
            return $this->dataResponse('success', 200, 'Produced Item ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Produced Item ' . __('msg.record_not_found'));
        }
    }

    public function onWarehouseReceiveItem($scannedItem, $createdById, $temporaryStorageId)
    {
        try {
            $warehouseReferenceNo = WarehouseReceivingModel::onGenerateWarehouseReceiveReferenceNumber();
            $currentBatchId = null;
            $itemsToTransfer = [];

            foreach ($scannedItem as $value) {
                $currentBatchId = $value['bid'];
                $currentStickerNo = $value['sticker_no'];

                $productionBatch = ProductionBatchModel::find($currentBatchId);
                $productionBatch->has_endorsement_from_qa = 0;
                $productionBatch->save();
                $batchNumber = $productionBatch->batch_number ?? null;
                $itemCode = $productionBatch->productionOta->item_code ?? $productionBatch->productionOtb->item_code;
                $skuType = $productionBatch->productionOta->itemMasterdata->itemCategory->name ?? $productionBatch->productionOtb->itemMasterdata->itemCategory->name;
                $productionOrderId = $productionBatch->productionOrder->id;
                $productionItems = $productionBatch->productionItems;
                $producedItems = json_decode($productionItems->produced_items, true);
                $inclusionArray = [0, 9];

                $warehouseReceivingArr = [
                    'warehouse_receiving' => ['reference_number' => $warehouseReferenceNo]
                ];
                $producedItems[$currentStickerNo]['warehouse'] = $warehouseReceivingArr;
                $productionItems->produced_items = json_encode($producedItems);
                $productionItems->save();

                $flag = $this->onItemCheckHoldInactiveDone($producedItems, $currentStickerNo, $inclusionArray, []);
                if (!isset($itemsToTransfer[$currentBatchId])) {
                    $itemsToTransfer[$currentBatchId] = [
                        'production_order_id' => $productionOrderId,
                        'production_item_id' => $productionBatch->productionItems->id,
                        'batch_id' => $currentBatchId,
                        'batch_number' => $batchNumber,
                        'item_code' => $itemCode,
                        'sku_type' => $skuType,
                        'qty' => 0,
                        'flag' => $flag,
                        'item' => []
                    ];
                }
                $producedItems[$currentStickerNo]['status'] = 2;
                $itemsToTransfer[$currentBatchId]['qty']++;
                $itemsToTransfer[$currentBatchId]['item'][$currentStickerNo] = $producedItems[$currentStickerNo];
            }


            DB::beginTransaction();
            if ($temporaryStorageId != null) {
                if (!$this->onCheckAvailability($temporaryStorageId, false)) {
                    throw new Exception('Sub Location Unavailable');
                }
                $this->onQueueStorage($createdById, $scannedItem, $temporaryStorageId, false);
            }

            foreach ($itemsToTransfer as $key => $value) {
                if ($value['flag']) {
                    $warehouseReceive = new WarehouseReceivingModel();
                    $warehouseReceive->reference_number = $warehouseReferenceNo;
                    $warehouseReceive->production_order_id = $value['production_order_id'];
                    $warehouseReceive->production_batch_id = $value['batch_id'];
                    $warehouseReceive->batch_number = $value['batch_number'];
                    $warehouseReceive->produced_items = json_encode($value['item']);
                    $warehouseReceive->item_code = $value['item_code'];
                    $warehouseReceive->sku_type = $value['sku_type'];
                    $warehouseReceive->quantity = $value['qty'];
                    $warehouseReceive->created_by_id = $createdById;
                    $warehouseReceive->save();
                    $this->createWarehouseLog(ProductionItemModel::class, $itemsToTransfer[$key]['production_item_id'], WarehouseReceivingModel::class, $warehouseReceive->id, $warehouseReceive->getAttributes(), $createdById, 0);
                }
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }
}


