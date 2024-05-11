<?php

namespace App\Http\Controllers\v1\QualityAssurance;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProducedItemModel;
use App\Models\Productions\ProductionBatchModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use DB;
use App\Traits\CrudOperationsTrait;
use App\Traits\ProductionHistoricalLogTrait;

class ItemDispositionController extends Controller
{
    use CrudOperationsTrait, ProductionHistoricalLogTrait;
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'created_by_id' => 'required',
            'action_status_id' => 'required|integer|in:6,7,8',
            'aging_period' => 'required|integer',
            'quantity_update' => 'required_if:action_status_id,7,8|integer'
        ];
        // 6 = For Retouch, 7 = For Slice, 8 = For Sticker Update
        $fields = $request->validate($rules);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $itemDisposition = ItemDispositionModel::find($id);
            $producedItemModel = ProducedItemModel::where('production_batch_id', $itemDisposition->production_batch_id)->first();
            $producedItems = json_decode($producedItemModel->produced_items, true);
            $producedItems[$itemDisposition->item_key]['status'] = $fields['action_status_id'];
            if ($fields['action_status_id'] == 8) {
                $producedItems[$itemDisposition->item_key]['q'] = $fields['quantity_update'];
            }
            $producedItemModel->produced_items = json_encode($producedItems);
            $producedItemModel->save();
            $this->createProductionHistoricalLog(ProducedItemModel::class, $producedItemModel->id, $producedItems[$itemDisposition->item_key], $createdById, 1, $itemDisposition->item_key);

            $itemDisposition->produced_items = json_encode([$itemDisposition->item_key => $producedItems[$itemDisposition->item_key]]);
            $itemDisposition->quantity_update = $fields['quantity_update'] ?? null;
            $itemDisposition->aging_period = $fields['aging_period'];
            $itemDisposition->updated_by_id = $fields['created_by_id'];
            $itemDisposition->updated_at = now();
            $itemDisposition->action = $fields['action_status_id'];
            $itemDisposition->save();
            $this->createProductionHistoricalLog(ItemDispositionModel::class, $itemDisposition->id, $itemDisposition, $createdById, 1, $itemDisposition->item_key);
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetall()
    {
        return $this->readRecord(ItemDispositionModel::class, 'Item Disposition');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemDispositionModel::class, $id, 'Item Disposition');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemDispositionModel::class, $id, 'Item Disposition');
    }
    public function onCloseDisposition(Request $request, $id)
    {
        #region status list
        // 0 => 'Good',
        // 1 => 'On Hold',
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
        ]);
        try {
            // status to be excluded
            $createdById = $fields['created_by_id'];
            $triggerReviewedStatus = [6, 7, 8, 9, 11, 12];
            $itemBatches = ItemDispositionModel::where('production_batch_id', $id)->get();
            DB::beginTransaction();
            if (count($itemBatches) > 0) {
                foreach ($itemBatches as $items) {
                    $producedItemData = ProducedItemModel::where('production_batch_id', $items['production_batch_id'])->first();
                    $producedItems = json_decode($producedItemData->produced_items, true);
                    $statusItem = $producedItems[$items['item_key']]['status'];

                    if (!in_array($statusItem, $triggerReviewedStatus)) {
                        $producedItems[$items['item_key']]['status'] = 10;
                        $producedItems[$items['item_key']]['sticker_status'] = 0;
                    }
                    $producedItemData->produced_items = json_encode($producedItems);
                    $producedItemData->save();


                    $items->status = 0;
                    $items->production_status = 0;
                    $items->aging_period = $items->created_at->diffInDays(Carbon::now());
                    $items->save();
                    $this->createProductionHistoricalLog(ItemDispositionModel::class, $items->id, $items, $createdById, 1, $items['item_key']);
                }
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'));
            }
            return $this->dataResponse('error', 200, ItemDispositionModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetAllCategory($type = null, $status)
    {
        try {
            $itemDisposition = ItemDispositionModel::select('production_batch_id', 'is_release', DB::raw('count(*) as count'))
                ->with('productionBatch')
                ->where('status', $status)
                ->where('type', $type)
                ->groupBy([
                    'production_batch_id',
                    'is_release'
                ])
                ->get();
            $batchDisposition = [];
            $counter = 0;
            foreach ($itemDisposition as $value) {
                $batchDisposition[$counter] = [
                    'production_batch_id' => $value->production_batch_id,
                    'quantity' => $value->count,
                    'is_release' => $value->is_release,
                    'production_batch_number' => ProductionBatchModel::find($value->production_batch_id)->batch_number,
                    'production_orders_to_make' => $value->productionBatch->productionOtb ?? $value->productionBatch->productionOta
                ];
                ++$counter;
            }
            if (count($batchDisposition) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $batchDisposition);
            }
            return $this->dataResponse('error', 200, ItemDispositionModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetCurrent($id, $type = null)
    {
        try {
            $itemDisposition = ItemDispositionModel::where('production_batch_id', $id);
            if ($type != null) {
                $itemDisposition->where('type', $type);
            }
            $data = $itemDisposition->get();
            if (count($data) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $data);
            }
            return $this->dataResponse('error', 200, ItemDispositionModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onHoldRelease(Request $request, $id)
    {
        $fields = $request->validate([
            'is_release' => 'required|boolean',
            'created_by_id' => 'required'
        ]);
        try {
            $createdById = $fields['created_by_id'];
            $producedItem = ProducedItemModel::where('production_batch_id', $id)->first();
            $productionBatch = $producedItem->productionBatch;
            $itemDisposition = ItemDispositionModel::where('production_batch_id', $id)->get();
            if ($productionBatch) {
                DB::beginTransaction();
                $response = null;
                if ($fields['is_release']) {
                    $response = $this->onReleaseHoldStatus($producedItem, $productionBatch, $itemDisposition, $createdById);
                } else {
                    $response = $this->onHoldStatus($producedItem, $productionBatch, $itemDisposition, $createdById);
                }

                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $response);
            }
            return $this->dataResponse('error', 200, ItemDispositionModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onHoldStatus($producedItem, $productionBatch, $itemDisposition, $createdById)
    {
        try {
            $producedItemArray = json_decode($producedItem->produced_items);
            foreach ($producedItemArray as $key => $value) {
                if ($value->sticker_status === 1) {
                    if ($value->status !== 1) {
                        $value->prev_status = $value->status;
                    }
                    $value->status = 1;
                    $this->createProductionHistoricalLog(ProducedItemModel::class, $producedItem->id, $value, $createdById, 1, $key);
                }
            }

            foreach ($itemDisposition as $disposition) {
                $disposition->is_release = 0;
                $disposition->save();
                $this->createProductionHistoricalLog(ItemDispositionModel::class, $disposition->id, $disposition, $createdById, 1, $disposition->item_key);
            }
            $productionBatch->status = 1;
            $productionBatch->update();
            $this->createProductionHistoricalLog(ProductionBatchModel::class, $productionBatch->id, $productionBatch, $createdById, 1);
            $producedItem->produced_items = json_encode($producedItemArray);
            $producedItem->update();
            $this->createProductionHistoricalLog(ProducedItemModel::class, $producedItem->id, $producedItem, $createdById, 1);
            $response = [
                'status' => $productionBatch->statusLabel
            ];
            return $response;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }

    public function onReleaseHoldStatus($producedItem, $productionBatch, $itemDisposition, $createdById)
    {
        try {
            $producedItemArray = json_decode($producedItem->produced_items);
            foreach ($producedItemArray as $key => $value) {

                if ($value->sticker_status === 1) {
                    $value->status = $value->prev_status;
                    $this->createProductionHistoricalLog(ProducedItemModel::class, $producedItem->id, $value, $createdById, 1, $key);
                }
            }

            $productionBatch->status = 0;
            if ($productionBatch->productionOrder->status === 1) {
                $productionBatch->status = 2;
            }
            foreach ($itemDisposition as $disposition) {
                $disposition->is_release = 1;
                $disposition->save();
                $this->createProductionHistoricalLog(ItemDispositionModel::class, $disposition->id, $disposition, $createdById, 1, $disposition->item_key);
            }
            $productionBatch->update();
            $this->createProductionHistoricalLog(ProductionBatchModel::class, $productionBatch->id, $productionBatch, $createdById, 1);
            $producedItem->produced_items = json_encode($producedItemArray);
            $producedItem->update();
            $this->createProductionHistoricalLog(ProducedItemModel::class, $producedItem->id, $producedItem, $createdById, 1);
            $response = [
                'status' => $productionBatch->statusLabel
            ];
            return $response;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }

    public function onGetOverallStats(Request $request)
    {
        $fields = $request->validate([
            'start_date' => 'nullable|date',
            'range_date' => 'nullable|required_if:start_date,null|date',
        ]);
        try {
            // 4 => 'For Investigation',
            // 5 => 'For Sampling',
            // 6 => 'For Retouch',
            // 7 => 'For Slice',
            // 8 => 'For Sticker Update',
            // 9 => 'Sticker Updated',
            // 10 => 'Reviewed',
            // 11 => 'Retouched',
            // 12 => 'Sliced',
            $results = ItemDispositionModel::selectRaw('
                    SUM(CASE WHEN production_status = 1 AND action IS NULL THEN 1 ELSE 0 END) as for_review,
                    SUM(CASE WHEN production_status = 1 AND type = 0 AND action IS NULL THEN 1 ELSE 0 END) as for_investigation,
                    SUM(CASE WHEN production_status = 1 AND type = 1 AND action IS NULL THEN 1 ELSE 0 END) as for_sampling,
                    SUM(CASE WHEN production_status = 1 AND action IS NULL THEN 1 ELSE 0 END) as for_sampling,
                    SUM(CASE WHEN production_status = 1 AND action = 6 THEN 1 ELSE 0 END) as for_retouch,
                    SUM(CASE WHEN production_status = 1 AND action = 7 THEN 1 ELSE 0 END) as for_slice,
                    SUM(CASE WHEN production_status = 1 AND action = 8 THEN 1 ELSE 0 END) as for_sticker_update,
                    SUM(CASE WHEN production_status = 0 AND action = 8 THEN 1 ELSE 0 END) as sticker_updated,
                    SUM(CASE WHEN production_status = 0 AND action IS NULL THEN 1 ELSE 0 END) as reviewed,
                    SUM(CASE WHEN production_status = 0 AND action = 6 THEN 1 ELSE 0 END) as retouched,
                    SUM(CASE WHEN production_status = 0 AND action = 7 THEN 1 ELSE 0 END) as sliced
                ')
                ->whereDate('created_at', $fields['start_date'])
                ->first();
            return $this->dataResponse('success', 200, __('msg.update_success'), $results);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
