<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\GeneratePicklist;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationItemModel;
use App\Models\WMS\InventoryKeeping\AllocationOrder\AllocationOrderModel;
use App\Models\WMS\InventoryKeeping\GeneratePicklist\GeneratePickListItemModel;
use App\Models\WMS\InventoryKeeping\GeneratePicklist\GeneratePickListModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Http\Request;
use Exception;
use App\Traits\WMS\WarehouseLogTrait;
use App\Traits\MOS\ProductionLogTrait;
use DB;
use App\Traits\WMS\QueueSubLocationTrait;
class GeneratePickListItemController extends Controller
{
    use WarehouseLogTrait, ProductionLogTrait, QueueSubLocationTrait;
    public function onPickItems(Request $request)
    {
        $fields = $request->validate([
            'scanned_items' => 'required|json',
            'created_by_id' => 'required',
            'store_details' => 'nullable|json',
            'generate_picklist_id' => 'required|exists:wms_generate_picklists,id',
        ]);
        try {
            DB::beginTransaction();
            $scannedItems = json_decode($fields['scanned_items'], true);
            $storeDetails = json_decode($fields['store_details'] ?? '', true);
            $scannedItemsArray = [];
            $createdById = $fields['created_by_id'];
            foreach ($scannedItems as $items) {
                $productionBatchId = $items['bid'];
                $stickerNo = $items['sticker_no'];
                $itemId = $items['item_id'];
                $productionItemModel = ProductionItemModel::where('production_batch_id', $productionBatchId)->first();
                $producedItems = json_decode($productionItemModel->produced_items, true);
                if ($producedItems[$stickerNo]['status'] == 13) {
                    $producedItems[$stickerNo]['status'] = 15;
                    $productionItemModel->produced_items = json_encode($producedItems);
                    $productionItemModel->save();
                    $this->createProductionLog(ProductionItemModel::class, $productionItemModel->id, $producedItems[$stickerNo], $fields['created_by_id'], 0, $stickerNo);
                    if (isset($scannedItemsArray[$itemId])) {
                        $scannedItemsArray[$itemId]['picked_scanned_quantity'] += 1;
                        $scannedItemsArray[$itemId]['picked_scanned_items'][] = [
                            'bid' => $productionBatchId,
                            'sticker_no' => $stickerNo,
                        ];

                    } else {
                        $scannedItemsArray[$itemId] = [
                            'item_id' => $itemId,
                            'picked_scanned_quantity' => 1,
                            'picked_scanned_items' => [],
                            'scanned_by_id' => $fields['created_by_id'],
                        ];
                        $scannedItemsArray[$itemId]['picked_scanned_items'][] = [
                            'bid' => $productionBatchId,
                            'sticker_no' => $stickerNo,
                        ];
                    }
                }
            }
            // Generate Picklist Item store data
            $existingPicklistItem = GeneratePickListItemModel::where('generate_picklist_id', $fields['generate_picklist_id']);
            if ($storeDetails != null) {
                $existingPicklistItem->where('store_id', $storeDetails['store_id']);
            }
            $existingPicklistItem = $existingPicklistItem->first();

            if ($existingPicklistItem) {
                $this->onUpdateItems($existingPicklistItem, $scannedItemsArray, $createdById);
            } else {
                $this->onInitializeItems($fields['generate_picklist_id'], $storeDetails, $scannedItemsArray, $createdById);
            }
            DB::commit();
            return $this->dataResponse('success', 200, 'Generate Picklist ' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Generate Picklist Items ' . __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onInitializeItems($generatePicklistId, $storeDetails, $scannedItemsArray, $createdById)
    {
        try {
            $generatePicklistItemModel = new GeneratePickListItemModel();
            $generatePicklistItemModel->generate_picklist_id = $generatePicklistId;
            if ($storeDetails != null) {
                $generatePicklistItemModel->store_id = $storeDetails['store_id'];
                $generatePicklistItemModel->store_name = $storeDetails['store_name'];
            }
            $generatePicklistItemModel->picklist_items = json_encode($scannedItemsArray);
            $generatePicklistItemModel->created_by_id = $createdById;
            $generatePicklistItemModel->save();
            $this->createWarehouseLog(null, null, GeneratePickListItemModel::class, $generatePicklistItemModel->id, $generatePicklistItemModel->getAttributes(), $generatePicklistItemModel->created_by_id, 0);

            // Decrement stock from stock log, stock inventory, and queued sub location
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public function onUpdateItems($generatePickListModel, $scannedItemsArray, $createdById)
    {
        try {
            $pickedlistItems = json_decode($generatePickListModel->picklist_items, true);
            $mappedPickedItems = [];
            foreach ($pickedlistItems as $itemDetails) {
                $mappedPickedItems[$itemDetails['item_id']] = [];
                foreach ($itemDetails['picked_scanned_items'] as $pickedItems) {
                    $mappedPickedItems[$itemDetails['item_id']][] = $pickedItems['bid'] . '-' . $pickedItems['sticker_no'];
                }
            }

            foreach ($scannedItemsArray as $itemId => $itemArray) {
                if (!array_key_exists($itemId, $pickedlistItems)) {
                    $pickedlistItems[$itemId] = [
                        'item_id' => $itemId,
                        'picked_scanned_quantity' => 0,
                        'picked_scanned_items' => [],
                        'picked_scanned_by_id' => $createdById,
                    ];
                    $mappedPickedItems[$itemId] = [];
                }
                foreach ($itemArray['picked_scanned_items'] as $pickedItems) {
                    if (in_array($pickedItems['bid'] . '-' . $pickedItems['sticker_no'], $mappedPickedItems[$itemId])) {
                        continue;
                    } else {
                        $pickedlistItems[$itemId]['picked_scanned_quantity'] += 1;
                        $pickedlistItems[$itemId]['picked_scanned_items'][] = [
                            'bid' => $pickedItems['bid'],
                            'sticker_no' => $pickedItems['sticker_no'],
                        ];
                    }
                }
            }
            $generatePickListModel->picklist_items = json_encode($pickedlistItems);
            $generatePickListModel->save();
            $this->createWarehouseLog(null, null, GeneratePickListItemModel::class, $generatePickListModel->id, $generatePickListModel->getAttributes(), $createdById, 1);

            // Decrement stock from stock log, stock inventory, and queued sub location
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public function onStockmanRemovePickedItems(Request $request)
    {
        $fields = $request->validate([
            'scanned_items' => 'required|json',
            'item_id' => 'required',
            'store_id' => 'nullable',
            'generate_picklist_id' => 'required',
            'created_by_id' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $storeId = $fields['store_id'] ?? null;
            $generatePicklistId = $fields['generate_picklist_id'];
            $scannedItems = json_decode($fields['scanned_items'], true);
            $createdById = $fields['created_by_id'];
            $itemId = $fields['item_id'];
            $generatePicklistItemModel = GeneratePickListItemModel::where('generate_picklist_id', $generatePicklistId);
            if ($storeId != null) {
                $generatePicklistItemModel->where('store_id', $storeId);
            }
            $generatePicklistItemModel = $generatePicklistItemModel->first();

            if ($generatePicklistItemModel == null) {
                return $this->dataResponse('error', 400, 'Generate Picklist Item not found');
            }
            $pickedlistItems = json_decode($generatePicklistItemModel->picklist_items, true);
            $pickedListItemArray = [];
            foreach ($pickedlistItems[$itemId]['picked_scanned_items'] as $pickedItem) {
                $pickedListItemArray[$pickedItem['bid'] . '-' . $pickedItem['sticker_no']] = $pickedItem;
            }

            foreach ($scannedItems as $item) {
                $itemToRemove = $item['bid'] . '-' . $item['sticker_no'];
                if (array_key_exists($itemToRemove, $pickedListItemArray)) {
                    unset($pickedListItemArray[$itemToRemove]);
                }
            }
            $pickedlistItems[$itemId]['picked_scanned_items'] = array_values($pickedListItemArray);
            $pickedlistItems[$itemId]['picked_scanned_quantity'] = count($pickedListItemArray);
            $generatePicklistItemModel->picklist_items = json_encode($pickedlistItems);
            $generatePicklistItemModel->save();
            dd($generatePicklistItemModel);
            // Continue for store transfer items
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Generate Picklist Items ' . __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onGetRemoveItemData($item_id, $store_id, $generate_picklist_id)
    {
        try {
            $generatePicklistItemModel = GeneratePickListItemModel::where([
                'generate_picklist_id' => $generate_picklist_id,
            ])->get();
            $generatePicklistModel = GeneratePickListModel::find($generate_picklist_id);
            if (count($generatePicklistItemModel) > 0) {
                $allocationOrderModel = $generatePicklistModel->AllocationOrder->id;

                $storeList = [];
                foreach ($generatePicklistItemModel as $storeDetails) {
                    $picklistItem = json_decode($storeDetails['picklist_items'], true);
                    if (array_key_exists($item_id, $picklistItem)) {
                        $itemMasterData = ItemMasterdataModel::find($item_id);
                        $itemDetails = [
                            'item_id' => $item_id,
                            'item_description' => $itemMasterData->description,
                            'item_category' => $itemMasterData->item_category_label,
                            'attachment' => $itemMasterData->attachment,
                        ];
                        $storeList[$storeDetails['store_id']] = [
                            'item_details' => $itemDetails,
                            'store_id' => $storeDetails['store_id'],
                            'store_name' => $storeDetails['store_name'],
                            'picked_quantity' => $picklistItem[$item_id]['picked_scanned_quantity'],
                            'picked_items' => $picklistItem[$item_id]['picked_scanned_items'],
                        ];
                    }
                }
                $allocationItemModel = AllocationItemModel::where([
                    'item_id' => $item_id,
                    'allocation_order_id' => $allocationOrderModel,
                ])->first();
                $storeOrderDetails = json_decode($allocationItemModel->store_order_details, true);
                foreach ($storeOrderDetails as $storeId => $storeOrder) {
                    if (array_key_exists($storeId, $storeList)) {
                        if ($storeId != $store_id && ($storeList[$storeId]['picked_quantity'] >= $storeOrder['regular_order_quantity'])) {
                            unset($storeList[$storeId]);
                        } else {
                            $storeList[$storeId]['regular_order_quantity'] = $storeOrder['regular_order_quantity'];
                        }
                    }
                }
                return $this->dataResponse('success', 200, 'Generate Picklist Items ' . __('msg.record_found'), $storeList);
            }
            return $this->dataResponse('error', 400, 'Generate Picklist Items ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Generate Picklist Items ' . __('msg.record_not_found'), $exception->getMessage());

        }
    }

    public function onCheckPickedItem(Request $request)
    {
        $fields = $request->validate([
            'generate_picklist_id' => 'required|exists:wms_generate_picklists,id',
            'scanned_items' => 'required|json',
            'created_by_id' => 'required',
            'store_details' => 'nullable|json',
            'picking_type' => 'required|in:0,1', // 0 = Discreet, 1 = Batch
            'batch_count' => 'nullable|json',
            'temporary_storage_id' => 'nullable',
        ]);
        try {
            DB::beginTransaction();
            $scannedItems = json_decode($fields['scanned_items'], true);
            $storeDetails = json_decode($fields['store_details'] ?? '', true);
            $createdById = $fields['created_by_id'];
            $temporaryStorageId = $fields['temporary_storage_id'] ?? null;
            // Generate Picklist Item store data
            $generatePickListModel = GeneratePickListItemModel::where('generate_picklist_id', $fields['generate_picklist_id']);
            if ($storeDetails != null) {
                $generatePickListModel->where('store_id', $storeDetails['store_id']);
            }
            $generatePickListModel = $generatePickListModel->first();

            $pickedlistItems = json_decode($generatePickListModel->picklist_items, true);
            $mappedPickedItems = [];
            foreach ($pickedlistItems as $itemDetails) {
                $mappedPickedItems[$itemDetails['item_id']] = [];
                foreach ($itemDetails['picked_scanned_items'] as $pickedItems) {
                    $mappedPickedItems[$itemDetails['item_id']][] = $pickedItems['bid'] . '-' . $pickedItems['sticker_no'];
                }
            }
            if ($fields['picking_type'] == 0) {
                $this->onDiscreetChecking($generatePickListModel, $pickedlistItems, $scannedItems, $temporaryStorageId, $mappedPickedItems, $createdById);
            } else {
                $batchCount = json_decode($fields['batch_count'], true);
                $this->onBatchChecking($generatePickListModel, $pickedlistItems, $batchCount, $createdById);
            }

            DB::commit();

            return $this->dataResponse('success', 200, 'Generate Picklist ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Generate Picklist Items ' . __('msg.update_failed'), $exception->getMessage());
        }
    }

    public function onDiscreetChecking($generatePickListModel, $pickedlistItems, $scannedItems, $temporaryStorageId, $mappedPickedItems, $createdById)
    {
        try {
            $forTemporaryStorageItems = [];
            foreach ($scannedItems as $pickedItems) {
                $itemId = $pickedItems['item_id'];
                $stickerNo = $pickedItems['sticker_no'];
                $productionBatchId = $pickedItems['bid'];
                // $productionBatchModel = ProductionBatchModel::find($pickedItems['bid']);
                // $picklistType = $productionBatchModel->itemMasterData->picklist_type;
                $productionItemModel = ProductionItemModel::where('production_batch_id', $pickedItems['bid'])->first();
                $producedItems = json_decode($productionItemModel->produced_items, true);
                if ($producedItems[$pickedItems['sticker_no']]['status'] == 15) {
                    $producedItems[$pickedItems['sticker_no']]['status'] = 15.1;
                    $productionItemModel->produced_items = json_encode($producedItems);
                    $productionItemModel->save();
                    $this->createProductionLog(ProductionItemModel::class, $productionItemModel->id, $producedItems[$stickerNo], $createdById, 0, $stickerNo);
                    if (isset($pickedlistItems[$itemId]) && in_array($productionBatchId . '-' . $stickerNo, $mappedPickedItems[$itemId])) {
                        if (!isset($pickedlistItems[$itemId]['checked_scanned_quantity'])) {
                            $pickedlistItems[$itemId]['checked_scanned_quantity'] = 1;
                            $pickedlistItems[$itemId]['checked_scanned_items'][] = [
                                'bid' => $productionBatchId,
                                'sticker_no' => $stickerNo,
                                'temporary_storage_id' => $temporaryStorageId,
                            ];
                            $pickedlistItems[$itemId]['checked_scanned_by'] = $createdById;
                            $forTemporaryStorageItems[] = [
                                'bid' => $productionBatchId,
                                'sticker_no' => $stickerNo,
                                'item_id' => $itemId,
                            ];
                        } else {
                            $pickedlistItems[$itemId]['checked_scanned_quantity'] += 1;
                            $pickedlistItems[$itemId]['checked_scanned_items'][] = [
                                'bid' => $productionBatchId,
                                'sticker_no' => $stickerNo,
                                'temporary_storage_id' => $temporaryStorageId,
                            ];
                            $forTemporaryStorageItems[] = [
                                'bid' => $productionBatchId,
                                'sticker_no' => $stickerNo,
                                'item_id' => $itemId,
                            ];
                        }
                    }
                }

            }
            if (count($forTemporaryStorageItems) > 0) {
                if ($temporaryStorageId != null) {
                    if (!$this->onCheckAvailability($temporaryStorageId, false)) {
                        throw new Exception('Sub Location Unavailable');
                    }
                    $generatePickListModel->picklist_items = json_encode($pickedlistItems);
                    $generatePickListModel->save();
                    $this->createWarehouseLog(null, null, GeneratePickListItemModel::class, $generatePickListModel->id, $generatePickListModel->getAttributes(), $createdById, 1);

                    $this->onQueueStorage($createdById, $forTemporaryStorageItems, $temporaryStorageId, false);
                }
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function onBatchChecking($generatePickListModel, $pickedlistItems, $batchCount, $createdById)
    {
        try {
            foreach ($batchCount as $pickedItems) {
                $itemId = $pickedItems['item_id'];
                $checkedQuantityCount = $pickedItems['checked_quantity_count'];
                $pickedlistItems[$itemId]['checked_scanned_quantity'] = $checkedQuantityCount;
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}

/*
    0 => 'Good',
    1 => 'On Hold',
    "1.1" => 'On Hold - Sub Standard',
    2 => 'For Receive',
    "2.1" => 'For Receive - In Process',
    3 => 'Received',
    "3.1" => 'For Put-away - In Process',
    4 => 'For Investigation',
    5 => 'For Sampling',
    6 => 'For Retouch',
    7 => 'For Slice',
    8 => 'For Sticker Update',
    9 => 'Sticker Updated',
    10 => 'Reviewed',
    10.1 => 'For Store Distribution',
    10.2 => 'For Disposal',
    10.3 => 'For Intersell',
    10.4 => 'For Complimentary',
    11 => 'Retouched',
    12 => 'Sliced',
    13 => 'Stored',
    14 => 'For Transfer',
    15 => 'Picked',
    15.1 => 'Checked',
    15.2 => 'For Dispatch',
*/
