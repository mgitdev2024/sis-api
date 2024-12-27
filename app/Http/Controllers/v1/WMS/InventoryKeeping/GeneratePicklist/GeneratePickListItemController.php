<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\GeneratePicklist;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\WMS\InventoryKeeping\GeneratePicklist\GeneratePickListItemModel;
use Illuminate\Http\Request;
use Exception;
use App\Traits\WMS\WarehouseLogTrait;
use App\Traits\MOS\ProductionLogTrait;
use DB;
class GeneratePickListItemController extends Controller
{
    use WarehouseLogTrait, ProductionLogTrait;
    public function onScanItems(Request $request)
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
            return $this->dataResponse('success', 200, 'Generate Picklist ' . __('msg.record_found'));
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
            dd($mappedPickedItems);
        } catch (Exception $exception) {
            dd($exception);
            throw $exception;
        }
    }
}
