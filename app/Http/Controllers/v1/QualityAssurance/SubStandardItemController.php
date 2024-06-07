<?php

namespace App\Http\Controllers\v1\QualityAssurance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\MOS\Production\ProductionItemController;
use App\Models\MOS\Production\ProductionBatchModel;
use Illuminate\Http\Request;
use App\Models\QualityAssurance\SubStandardItemModel;
use App\Traits\WmsCrudOperationsTrait;
use App\Traits\ProductionLogTrait;

use Exception;
use DB;

class SubStandardItemController extends Controller
{
    use WmsCrudOperationsTrait, ProductionLogTrait;
    public function onCreate(Request $request)
    {
        #region status list
        // 0 => 'Good',
        // 1 => 'On Hold',
        // 1.1 => 'On Hold - Sub Standard
        // 2 => 'For Receive',
        // 3 => 'Received',
        // 4 => 'For Investigation',
        // 5 => 'For Sampling',
        // 6 => 'For Retouch',
        // 7 => 'For Slice',
        // 8 => 'For Sticker Update',
        // 9 => 'Sticker Updated',
        // 10 => 'Reviewed',
        // 11 => 'Retouched',
        // 12 => 'Sliced',
        #endregion
        $fields = $request->validate([
            'created_by_id' => 'required',
            'scanned_items' => 'required',
            'reason' => 'required',
            'attachment' => 'nullable',
            'location_id' => 'required|integer|between:1,5',
        ]);
        try {
            DB::beginTransaction();
            $scannedItems = json_decode($fields['scanned_items'], true);
            $createdById = $fields['created_by_id'];
            foreach ($scannedItems as $value) {
                $productionBatch = ProductionBatchModel::find($value['bid']);
                if (!$productionBatch) {
                    continue;
                }
                $subStandardItem = SubStandardItemModel::where('production_batch_id', $value['bid'])
                    ->where('item_key', $value['sticker_no'])
                    ->where('status', 1)
                    ->first();
                if ($subStandardItem) {
                    continue;
                }
                $record = new SubStandardItemModel();
                $record->item_key = $value['sticker_no'];
                $record->production_batch_id = $value['bid'];
                $record->production_type = $productionBatch->productionItems->production_type;
                $record->location_id = $fields['location_id'];

                $record->reason = $fields['reason'];
                if ($request->hasFile('attachment')) {
                    $attachmentPath = $request->file('attachment')->store('public/attachments/substandard-items');
                    $filepath = 'storage/' . substr($attachmentPath, 7);
                    $record->attachment = $filepath;
                }
                $record->created_by_id = $createdById;
                $record->save();

                $productionItemModel = $productionBatch->productionItems;
                $producedItems = json_decode($productionItemModel->produced_items, true);
                $producedItems[$value['sticker_no']]['status'] = 1.1;
                $productionItemModel->produced_items = json_encode($producedItems);
                $productionItemModel->save();
                $this->createProductionLog(SubStandardItemModel::class, $record->id, $record, $createdById, 1, $value['sticker_no']);
            }

            DB::commit();
            return $this->dataResponse('success', 201, 'Sub-Standard ' . __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, 'Sub-Standard ' . __('msg.create_failed'));
        }
    }

    public function onGetCurrent($status)
    {
        try {
            $whereFields = [];
            if ($status != null) {
                $whereFields = [
                    'status' => $status
                ];
            }
            return $this->readCurrentRecord(SubStandardItemModel::class, $status, $whereFields, null, null, 'Sub-Standard');
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, 'Sub-Standard ' . __('msg.record_not_found'));
        }
    }

    public function onGetNotification()
    {
        try {
            $subStandard = SubStandardItemModel::select('location_id', DB::raw('count(*) as count'))
                ->where('status', 1)
                ->groupBy('location_id')
                ->get();

            $data = [];
            foreach ($subStandard as $subStandardItem) {
                $data[] = [
                    'location_label' => $subStandardItem->location_label,
                    'count' => $subStandardItem->count
                ];
            }

            return $this->dataResponse('success', 201, 'Sub-Standard ' . __('msg.record_found'), $data);

        } catch (Exception $exception) {

            return $this->dataResponse('error', 400, 'Sub-Standard ' . __('msg.record_not_found'), $exception);
        }
    }
    // public function onUpdateById(Request $request, $id)
    // {
    //     $fields = $request->validate([
    //         'created_by_id' => 'required',
    //     ]);
    //     try {
    //         DB::beginTransaction();
    //         $subStandardItem = SubStandardItemModel::find($id);
    //         if (!$subStandardItem) {
    //             return $this->dataResponse('error', 400, 'Sub-Standard ' . __('msg.record_not_found'));

    //         }
    //         $productionbatch = ProductionBatchModel::find($subStandardItem->production_batch_id);
    //         $productionItem = json_decode($productionbatch->productionItems->produced_items, true);
    //         $itemKey = $subStandardItem->item_key;

    //         $productionType = $subStandardItem->production_type;
    //         $productionItemController = new ProductionItemController();
    //         $container = $productionItemController->onItemDisposition($fields['created_by_id'], $subStandardItem->production_batch_id, $productionItem[$itemKey], $itemKey, 4, $productionType);

    //         $subStandardItem->item_disposition_id = $container->id;
    //         $subStandardItem->status = 0;
    //         $subStandardItem->save();
    //         DB::commit();
    //         return $this->dataResponse('success', 200, 'Sub-Standard ' . __('msg.update_success'));
    //     } catch (Exception $exception) {
    //         DB::rollBack();
    //         return $this->dataResponse('error', 400, 'Sub-Standard ' . __('msg.update_failed'));
    //     }

    // }
}
