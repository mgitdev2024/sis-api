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

class ItemDispositionController extends Controller
{
    use CrudOperationsTrait;
    public function onUpdateById(Request $request, $id)
    {
        $token = $request->bearerToken();
        $this->authenticateToken($token);
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
            $itemDisposition = ItemDispositionModel::find($id);
            $producedItemModel = ProducedItemModel::where('production_batch_id', $itemDisposition->production_batch_id)->first();
            $producedItems = json_decode($producedItemModel->produced_items, true);
            $producedItems[$itemDisposition->item_key]['status'] = $fields['action_status_id'];
            if ($fields['action_status_id'] == 8) {
                $producedItems[$itemDisposition->item_key]['q'] = $fields['quantity_update'];
            }
            $producedItemModel->produced_items = json_encode($producedItems);
            $producedItemModel->save();

            $itemDisposition->produced_items = json_encode([$itemDisposition->item_key => $producedItems[$itemDisposition->item_key]]);
            $itemDisposition->quantity_update = $fields['quantity_update'] ?? null;
            $itemDisposition->aging_period = $fields['aging_period'];
            $itemDisposition->updated_by_id = $fields['created_by_id'];
            $itemDisposition->updated_at = now();
            $itemDisposition->action = $fields['action_status_id'];
            $itemDisposition->save();
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetall(Request $request)
    {
        return $this->readRecord(ItemDispositionModel::class,$request, 'Item Disposition');
    }
    public function onGetById(Request $request,$id)
    {
        return $this->readRecordById(ItemDispositionModel::class, $id, $request,'Item Disposition');
    }
    public function onDeleteById(Request $request,$id)
    {
        return $this->deleteRecordById(ItemDispositionModel::class, $id, $request,'Item Disposition');
    }
    public function onCloseDisposition(Request $request,$id)
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
        $token = $request->bearerToken();
        $this->authenticateToken($token);
        try {
            // status to be excluded
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

    public function onGetAllCategory(Request $request,$type = null, $status)
    {

        $token = $request->bearerToken();
        $this->authenticateToken($token);
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

    public function onGetCurrent(Request $request,$id, $type = null)
    {
        $token = $request->bearerToken();
        $this->authenticateToken($token);
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

    public function onHoldRelease(Request $request,$id)
    {
        $token = $request->bearerToken();
        $this->authenticateToken($token);
        $fields = $request->validate([
            'is_release' => 'required|boolean'
        ]);
        try {
            $producedItem = ProducedItemModel::where('production_batch_id', $id)->first();
            $productionBatch = $producedItem->productionBatch;
            $itemDisposition = ItemDispositionModel::where('production_batch_id', $id)->get();
            if ($productionBatch) {
                DB::beginTransaction();
                $response = null;
                if ($fields['is_release']) {
                    $response = $this->onReleaseHoldStatus($producedItem, $productionBatch, $itemDisposition);
                } else {
                    $response = $this->onHoldStatus($producedItem, $productionBatch, $itemDisposition);
                }

                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $response);
            }
            return $this->dataResponse('error', 200, ProductionBatchModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onHoldStatus($producedItem, $productionBatch, $itemDisposition)
    {
        try {
            $producedItemArray = json_decode($producedItem->produced_items);
            foreach ($producedItemArray as $value) {
                if ($value->sticker_status === 1) {
                    if ($value->status !== 1) {
                        $value->prev_status = $value->status;
                    }
                    $value->status = 1;
                }
            }

            foreach ($itemDisposition as $disposition) {
                $disposition->is_release = 0;
                $disposition->save();
            }
            $productionBatch->status = 1;
            $productionBatch->update();
            $producedItem->produced_items = json_encode($producedItemArray);
            $producedItem->update();
            $response = [
                'status' => $productionBatch->statusLabel
            ];
            return $response;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }

    public function onReleaseHoldStatus($producedItem, $productionBatch, $itemDisposition)
    {
        try {
            $producedItemArray = json_decode($producedItem->produced_items);
            foreach ($producedItemArray as $value) {
                if ($value->sticker_status === 1) {
                    $value->status = $value->prev_status;
                }
            }

            $productionBatch->status = 0;
            if ($productionBatch->productionOrder->status === 1) {
                $productionBatch->status = 2;
            }
            foreach ($itemDisposition as $disposition) {
                $disposition->is_release = 1;
                $disposition->save();
            }
            $productionBatch->update();
            $producedItem->produced_items = json_encode($producedItemArray);
            $producedItem->update();
            $response = [
                'status' => $productionBatch->statusLabel
            ];
            return $response;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }

    public function onGetOverallStats()
    {
        // For QA statistics only
    }
}
