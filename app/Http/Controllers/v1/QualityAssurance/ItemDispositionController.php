<?php

namespace App\Http\Controllers\v1\QualityAssurance;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProducedItemModel;
use App\Models\Productions\ProductionBatchModel;
use App\Models\Productions\ProductionOTBModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use Illuminate\Http\Request;
use Exception;
use DB;
use App\Traits\CrudOperationsTrait;

class ItemDispositionController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'production_order_id' => 'required|exists:production_orders,id',
            'item_code' => 'required|string',
            'production_date' => 'required|date_format:Y-m-d',
        ];
    }
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'plotted_quantity' => 'required|integer',
            'actual_quantity' => 'nullable|integer',
        ];
        return $this->updateRecordById(ItemDispositionModel::class, $request, $rules, 'Item Disposition', $id);
    }
    public function onGetAll()
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
    public function onCloseDisposition($id)
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

    public function onGetAllCategory($type = null, $status)
    {
        try {
            $itemDisposition = ItemDispositionModel::select('production_batch_id', DB::raw('count(*) as count'))
                ->with('productionBatch')
                ->where('status', $status)
                ->where('type', $type)
                ->groupBy('production_batch_id')
                ->get();
            $batchDisposition = [];
            $counter = 0;
            foreach ($itemDisposition as $value) {
                $batchDisposition[$counter] = [
                    'production_batch_id' => $value->production_batch_id,
                    'quantity' => $value->count,
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

    public function onGetCurrent($id)
    {
        try {
            $itemDisposition = ItemDispositionModel::where('production_batch_id', $id)->get();
            if (count($itemDisposition) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $itemDisposition);
            }
            return $this->dataResponse('error', 200, ItemDispositionModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onHoldRelease($id, Request $request)
    {
        $fields = $request->validate([
            'is_release' => 'required|boolean'
        ]);
        try {

            $producedItem = ProducedItemModel::where('production_batch_id', $id)->first();
            $productionBatch = $producedItem->productionBatch;

            if ($productionBatch) {
                DB::beginTransaction();
                $response = null;
                if ($fields['is_release']) {
                    $response = $this->onReleaseHoldStatus($producedItem, $productionBatch);
                } else {
                    $response = $this->onHoldStatus($producedItem, $productionBatch);
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

    public function onHoldStatus($producedItem, $productionBatch)
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

    public function onReleaseHoldStatus($producedItem, $productionBatch)
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
}
