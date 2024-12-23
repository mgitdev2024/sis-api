<?php

namespace App\Http\Controllers\v1\WMS\InventoryKeeping\GeneratePicklist;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionItemModel;
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

            $data = [];
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
                if (isset($data[$itemId])) {
                    $data[$itemId]['scanned_quantity'] += 1;
                    $data[$itemId]['scanned_quantity_items'][] = [
                        'bid' => $productionBatchId,
                        'sticker_no' => $stickerNo,
                    ];
                } else {
                    $data[$itemId] = [
                        'item_id' => $itemId,
                        'scanned_quantity' => 1,
                        'scanned_quantity_items' => [],
                        'scanned_by_id' => $fields['created_by_id'],
                    ];
                }
            }
            DB::commit();
            dd($data);
            return $this->dataResponse('success', 200, 'Generate Picklist ' . __('msg.record_found'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Generate Picklist Items ' . __('msg.update_failed'), $exception->getMessage());
        }
    }
}
