<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\GeneratePicklist;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\InventoryKeeping\GeneratePicklist\GeneratePickListItemModel;
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
                $generatePicklistItemModel->store_code = $storeDetails['store_code'];
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

    public function onCheckPickedItem(Request $request)
    {
        $fields = $request->validate([
            'generate_picklist_id' => 'required|exists:wms_generate_picklists,id',
            'scanned_items' => 'required|json',
            'created_by_id' => 'required',
            'store_details' => 'nullable|json',
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
                    $this->createProductionLog(ProductionItemModel::class, $productionItemModel->id, $producedItems[$stickerNo], $fields['created_by_id'], 0, $stickerNo);
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
                    $this->onQueueStorage($createdById, $forTemporaryStorageItems, $temporaryStorageId, false);
                }
            }

            $this->createWarehouseLog(null, null, GeneratePickListItemModel::class, $generatePickListModel->id, $generatePickListModel->getAttributes(), $createdById, 1);
            DB::commit();

            return $this->dataResponse('success', 200, 'Generate Picklist ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Generate Picklist Items ' . __('msg.update_failed'), $exception->getMessage());
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
