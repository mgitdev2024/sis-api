<?php

namespace App\Http\Controllers\v1\QualityAssurance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\MOS\Production\ProductionItemController;
use App\Models\MOS\Production\ProductionItemModel;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionOrderModel;
use App\Models\MOS\Production\ProductionOTAModel;
use App\Models\MOS\Production\ProductionOTBModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use App\Models\QualityAssurance\ItemDispositionRepositoryModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use DB;
use App\Traits\MOS\MosCrudOperationsTrait;
use App\Traits\MOS\ProductionLogTrait;

class ItemDispositionController extends Controller
{
    #region status list
    // 0 => 'Good',
    // 1 => 'On Hold',
    // 1.1 => 'On Hold - Sub Standard
    // 2 => 'For Receive | Return to warehouse',
    // 2.1 => 'For Receive - In Process',
    // 3 => 'Received',
    // 3.1 => 'For Put-away - In Process',
    // 4 => 'For Investigation',
    // 5 => 'For Sampling',
    // 6 => 'For Retouch',
    // 7 => 'For Slice',
    // 8 => 'For Sticker Update',
    // 9 => 'Sticker Updated',
    // 10 => 'Reviewed',
    // 10.1 => 'For Store Distribution',
    // 10.2 => 'For Disposal',
    // 10.3 => 'For Intersell',
    // 10.4 => 'For Complimentary',
    // 11 => 'Retouched',
    // 12 => 'Sliced',
    // 13 => 'Stored',
    // 14 => 'For Transfer',
    #endregion
    use MosCrudOperationsTrait, ProductionLogTrait;
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'created_by_id' => 'required',
            'action_status_id' => 'required|in:6,7,8,10.1,10.2,10.3,10.4',
            'aging_period' => 'required|integer',
            'quantity_update' => 'required_if:action_status_id,7,8|integer',
            'quantity_qa_for_repository' => 'required_if:action_status_id,7,8|integer',
            'type_qa_for_repository' => 'nullable|in:0,1,2,3', // 0 = For Disposal, 1 = For Intersell, 2 = For Store Distribution, 3 = For Complimentary
        ];
        // 6 = For Retouch, 7 = For Slice, 8 = For Sticker Update,
        // 2 = Return to warehouse, 10.1 = For Store Distribution, 10.2 = For Disposal, 10.3 = For Intersell, 10.4 = For Complimentary
        $fields = $request->validate($rules);
        try {
            DB::beginTransaction();
            $createdById = $fields['created_by_id'];
            $itemDisposition = ItemDispositionModel::find($id);
            $otbItems = $itemDisposition->productionBatch->productionOtb->itemMasterdata ?? null;
            $otaItems = $itemDisposition->productionBatch->productionOta->itemMasterdata ?? null;
            $itemMasterdata = $otbItems ?? $otaItems;
            $itemVariantType = $itemMasterdata->item_variant_type_id;

            $baseCode = explode(' ', $itemDisposition->item_code)[0];
            $parentItemCollection = ItemMasterdataModel::where('item_code', 'like', $baseCode . '%')
                ->whereNotNull('parent_item_id')
                ->where('item_variant_type_id', 3)->first();
            $isNotSliceable = true;
            if ($parentItemCollection) {
                $parentIds = json_decode($parentItemCollection->parent_item_id, true);
                if (in_array($itemMasterdata->id, $parentIds)) {
                    $isNotSliceable = false;
                }
            }

            $forItemRepositoryArr = [10.1, 10.2, 10.3, 10.4];
            $quantityUpdate = $fields['quantity_update'] ?? null;
            $producedItemModel = ProductionItemModel::where('production_batch_id', $itemDisposition->production_batch_id)->first();
            $producedItems = json_decode($producedItemModel->produced_items, true);
            $producedItems[$itemDisposition->item_key]['status'] = $fields['action_status_id'];
            if ($fields['action_status_id'] == 8) {
                $producedItems[$itemDisposition->item_key]['q'] = $fields['quantity_update'];
            } else if ($fields['action_status_id'] == 7 && /*(($itemVariantType != 1 || $itemVariantType != 10)) &&*/ $isNotSliceable) {
                return $this->dataResponse('error', 400, 'This item cannot be sliced');
            } else if ($fields['action_status_id'] == 6) {
                $quantityUpdate = 0;
            } else if (in_array($fields['action_status_id'], $forItemRepositoryArr)) {
                $quantityUpdate = 0;

                $productionBatchModel = $producedItemModel->productionBatch;
                $modelClass = $productionBatchModel->productionOtb
                    ? ProductionOTBModel::class
                    : ProductionOTAModel::class;
                $productionToBakeAssemble = $productionBatchModel->productionOtb ?? $productionBatchModel->productionOta;
                $productionToBakeAssemble->in_qa_count -= 1;
                $productionToBakeAssemble->save();
                $this->createProductionLog($modelClass, $productionToBakeAssemble->id, $productionToBakeAssemble->getAttributes(), $createdById, 1);
            }

            $forItemRepository = [7, 8, 10.1, 10.2, 10.3, 10.4];
            if (in_array($fields['action_status_id'], $forItemRepository)) {
                $itemDisposition->item_repository_type = $fields['type_qa_for_repository'];

                $this->onQaItemDispositionRepository($itemDisposition, $fields['quantity_qa_for_repository'], $fields['type_qa_for_repository'], $createdById);
            }

            $producedItemModel->produced_items = json_encode($producedItems);
            $producedItemModel->save();
            $this->createProductionLog(ProductionItemModel::class, $producedItemModel->id, $producedItems[$itemDisposition->item_key], $createdById, 1, $itemDisposition->item_key);
            $itemDisposition->produced_items = json_encode([$itemDisposition->item_key => $producedItems[$itemDisposition->item_key]]);
            $itemDisposition->quantity_update = $quantityUpdate;
            $itemDisposition->aging_period = $fields['aging_period'];
            $itemDisposition->updated_by_id = $fields['created_by_id'];
            $itemDisposition->updated_at = now();
            $itemDisposition->action = $fields['action_status_id'];
            $itemDisposition->save();
            $this->createProductionLog(ItemDispositionModel::class, $itemDisposition->id, $itemDisposition->getAttributes(), $createdById, 1, $itemDisposition->item_key);
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
        $fields = $request->validate([
            'created_by_id' => 'required',
            'item_disposition_type' => 'required|in:0,1'
        ]);
        try {
            // status to be excluded
            $createdById = $fields['created_by_id'];
            // $triggerReviewedStatus = [0, 2, 3, 6, 7, 8, 9, 11, 12];
            $triggerReviewedStatus = [1, 1.1, 4, 5];

            $productionBatch = ProductionBatchModel::find($id);
            $productionItems = $productionBatch->productionItems;
            $productionItemsArr = json_decode($productionItems->produced_items, true);
            $isTriggeredReviewedStatus = false;
            $triggeredReviewedStatusCount = 0;
            DB::beginTransaction();
            if ($productionBatch) {
                foreach ($productionItemsArr as $itemKey => &$items) {
                    $statusItem = $items['status'];
                    // $checkIfTriggerReviewedStatus = !in_array($statusItem, $triggerReviewedStatus);
                    $checkIfTriggerReviewedStatus = in_array($statusItem, $triggerReviewedStatus);

                    if ($checkIfTriggerReviewedStatus) {
                        $isTriggeredReviewedStatus = true;
                        $triggeredReviewedStatusCount++;
                        $items['status'] = 10;
                        $items['sticker_status'] = 0;
                        $this->createProductionLog(ProductionItemModel::class, $productionItems->id, $items, $createdById, 1, $itemKey);
                    }

                    $itemDisposition = ItemDispositionModel::where([
                        'production_batch_id' => $items['bid'],
                        'item_key' => $itemKey,
                        'type' => intval($fields['item_disposition_type']),
                        'status' => 1
                    ])
                        ->first();

                    if ($itemDisposition) {
                        $itemDisposition->status = 0;
                        // $itemDisposition->production_status = 0;
                        $itemDisposition->aging_period = $itemDisposition->created_at->diffInDays(Carbon::now());
                        if ($checkIfTriggerReviewedStatus) {
                            $itemDisposition->action = 10;
                        }
                        $itemDisposition->save();
                        $this->createProductionLog(ItemDispositionModel::class, $itemDisposition->id, $itemDisposition->getAttributes(), $createdById, 1, $itemDisposition['item_key']);
                    }
                    unset($items);
                }
                $productionItems->produced_items = json_encode($productionItemsArr);
                $productionItems->save();

                /*
                if ($isTriggeredReviewedStatus) {
                    $productionToBakeAssemble = $productionBatch->productionOtb ?? $productionBatch->productionOta;
                    $modelClass = $productionBatch->productionOtb
                        ? ProductionOTBModel::class
                        : ProductionOTAModel::class;

                    // $productionToBakeAssemble->produced_items_count -= $triggeredReviewedStatusCount;
                    $productionToBakeAssemble->save();
                    $this->createProductionLog($modelClass, $productionToBakeAssemble->id, $productionToBakeAssemble->getAttributes(), $fields['created_by_id'], 1);
                }*/
                DB::commit();
                return $this->dataResponse('success', 200, 'Item Disposition ' . __('msg.update_success'));
            } else {
                return $this->dataResponse('error', 200, 'Item Disposition ' . __('msg.record_not_found'));
            }
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onReopenDisposition(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'item_disposition_ids' => 'required|string'
        ]);
        try {// 0 = For Disposal, 1 = For Intersell, 2 = For Store Distribution, 3 = For Complimentary
            $createdById = $fields['created_by_id'];
            $itemDispositionIds = json_decode($fields['item_disposition_ids'], true);
            if (count($itemDispositionIds) > 0) {
                foreach ($itemDispositionIds as $itemDispositionId) {
                    $itemDispositionModel = ItemDispositionModel::find($itemDispositionId);
                    DB::beginTransaction();
                    $productionBatchModel = $itemDispositionModel->productionBatch;
                    $productionItemModel = $productionBatchModel->productionItems;
                    $dispositionedItem = json_decode($productionItemModel->produced_items, true);
                    $dispositionedItem[$itemDispositionModel->item_key]['status'] = $itemDispositionModel->type == 0 ? 4 : 5;
                    $dispositionedItem[$itemDispositionModel->item_key]['sticker_status'] = 1;
                    $actionArrayForIncrementQuantity = [7, 8, 9, 12];
                    $itemRepositoryModel = ItemDispositionRepositoryModel::where('item_disposition_id', $itemDispositionModel->id)->first();
                    if (in_array($itemDispositionModel->action, $actionArrayForIncrementQuantity)) {
                        $dispositionedQuantity = $itemRepositoryModel->quantity;
                        $dispositionedItem[$itemDispositionModel->item_key]['q'] += $dispositionedQuantity;
                    }
                    $itemRepositoryModel->delete();

                    $productionItemModel->produced_items = json_encode($dispositionedItem);
                    $productionItemModel->save();
                    $this->createProductionLog(ProductionItemModel::class, $productionItemModel->id, $dispositionedItem, $createdById, 1, $itemDispositionModel->item_key);
                    $itemDispositionModel->status = 1;
                    $itemDispositionModel->production_status = 1;
                    $itemDispositionModel->is_printed = 0;
                    $itemDispositionModel->action = null;
                    $itemDispositionModel->save();
                    $this->createProductionLog(ItemDispositionModel::class, $itemDispositionModel->id, $itemDispositionModel->getAttributes(), $createdById, 1, $itemDispositionModel->item_key);

                    DB::commit();
                }
                return $this->dataResponse('success', 200, 'Item Disposition ' . __('msg.update_success'));
            } else {
                return $this->dataResponse('error', 200, 'Item Disposition ' . __('msg.record_not_found'));
            }
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetAllCategory($type, $status, $filter = null)
    {
        try {
            $itemDisposition = ItemDispositionModel::select('production_batch_id', 'is_release', DB::raw('count(*) as count'))
                ->with('productionBatch')
                ->where('status', $status)
                ->where('type', $type);
            if ($filter != null) {
                $whereObject = \DateTime::createFromFormat('Y-m-d', $filter);
                if (($whereObject && $whereObject->format('Y-m-d') === $filter)) {
                    $itemDisposition->whereDate('created_at', $filter);
                }
            }
            $itemDisposition = $itemDisposition->groupBy([
                'production_batch_id',
                'is_release'
            ])
                ->get();
            $batchDisposition = [];
            $counter = 0;
            foreach ($itemDisposition as $value) {
                $productionBatchModel = ProductionBatchModel::find($value->production_batch_id);

                $batchDisposition[$counter] = [
                    'production_batch_id' => $value->production_batch_id,
                    'quantity' => $value->count,
                    'days_left' => [],
                    'is_release' => $value->is_release,
                    'production_batch_number' => $productionBatchModel->batch_number,
                    'production_batch_status' => ProductionOrderModel::find($productionBatchModel->production_order_id)->status,
                    'item_code' => $productionBatchModel->item_code,
                    'production_order_number' => $value->productionBatch->productionOrder->reference_number
                ];
                $today = Carbon::today();

                // Calculate the difference for each expiration date
                $ambientExpirationDate = $productionBatchModel->ambient_exp_date;
                $chilledExpirationDate = $productionBatchModel->chilled_exp_date;
                $frozenExpirationDate = $productionBatchModel->frozen_exp_date;

                // Subtract the expiration dates from today's date
                $ambientDiff = $today->diffInDays(Carbon::parse($ambientExpirationDate), false);
                $chilledDiff = $today->diffInDays(Carbon::parse($chilledExpirationDate), false);
                $frozenDiff = $today->diffInDays(Carbon::parse($frozenExpirationDate), false);

                if ($ambientExpirationDate) {
                    $batchDisposition[$counter]['days_left']['ambient_days_left'] = $ambientDiff;
                }
                if ($chilledExpirationDate) {
                    $batchDisposition[$counter]['days_left']['chilled_days_left'] = $chilledDiff;
                }
                if ($frozenExpirationDate) {
                    $batchDisposition[$counter]['days_left']['frozen_days_left'] = $frozenDiff;
                }
                ++$counter;
            }
            usort($batchDisposition, function ($a, $b) {
                $aDays = min($a['days_left'] ?? [0]);
                $bDays = min($b['days_left'] ?? [0]);
                return $aDays <=> $bDays;
            });
            if (count($batchDisposition) > 0) {
                return $this->dataResponse('success', 200, __('msg.record_found'), $batchDisposition);
            }
            return $this->dataResponse('error', 200, ItemDispositionModel::class . ' ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onGetCurrent($production_batch_id, $type = null)
    {
        try {
            $itemDisposition = ItemDispositionModel::select(
                'id',
                'quantity_update',
                'produced_items',
                'production_batch_id',
                'item_key',
                'item_code',
                'type',
                'production_type',
                'aging_period',
                'action',
                'status',
                'is_release',
                'created_at',
            )
                ->where('production_batch_id', $production_batch_id);
            if ($type != null) {
                $itemDisposition->where('type', $type);
            }
            $data = $itemDisposition->get();

            if (count($data) > 0) {
                $collections = [];
                foreach ($data as $value) {
                    $dataset = [];
                    $productionBatchModel = ProductionBatchModel::find($value['production_batch_id']);
                    $today = Carbon::today();

                    // Calculate the difference for each expiration date
                    $ambientExpirationDate = $productionBatchModel->ambient_exp_date;
                    $chilledExpirationDate = $productionBatchModel->chilled_exp_date;
                    $frozenExpirationDate = $productionBatchModel->frozen_exp_date;

                    // Subtract the expiration dates from today's date
                    $ambientDiff = $today->diffInDays(Carbon::parse($ambientExpirationDate), false);
                    $chilledDiff = $today->diffInDays(Carbon::parse($chilledExpirationDate), false);
                    $frozenDiff = $today->diffInDays(Carbon::parse($frozenExpirationDate), false);

                    if ($ambientExpirationDate) {
                        $dataset['days_left']['ambient_days_left'] = $ambientDiff;
                    }
                    if ($chilledExpirationDate) {
                        $dataset['days_left']['chilled_days_left'] = $chilledDiff;
                    }
                    if ($frozenExpirationDate) {
                        $dataset['days_left']['frozen_days_left'] = $frozenDiff;
                    }
                    $productionToBakeAssemble = $value->productionBatch->productionOta ?? $value->productionBatch->productionOtb;
                    $primaryConversionUnit = $productionToBakeAssemble->itemMasterdata->primaryConversion->long_name ?? null;
                    $dataset['id'] = $value['id'];
                    $dataset['item_variant_label'] = $productionToBakeAssemble->itemMasterdata->itemVariantType->name;
                    $dataset['is_sliceable_label'] = ItemDispositionModel::onIsSliceable($productionToBakeAssemble->itemMasterdata);
                    $dataset['quantity_update'] = $value['quantity_update'];
                    $dataset['produced_items'] = $value['produced_items'];
                    $dataset['production_batch_id'] = $value['production_batch_id'];
                    $dataset['item_key'] = $value['item_key'];
                    $dataset['item_code'] = $value['item_code'];
                    $dataset['type'] = $value['type'];
                    $dataset['production_type'] = $value['production_type'];
                    $dataset['aging_period'] = $value['aging_period'];
                    $dataset['action'] = $value['action'];
                    $dataset['status'] = $value['status'];
                    $dataset['is_release'] = $value['is_release'];
                    $dataset['created_at'] = $value['created_at'];
                    $dataset['batch_code'] = json_decode($value['produced_items'], true)[$value['item_key']]['batch_code'];
                    $dataset['can_sticker_update'] = strcasecmp($primaryConversionUnit, 'Pieces') == 0;
                    $dataset['scanned_date'] = date('Y-m-d (h:i:A)', strtotime($value->created_at));
                    $collections[] = $dataset;
                }
                usort($collections, function ($a, $b) {
                    $aDays = min($a['days_left'] ?? [0]);
                    $bDays = min($b['days_left'] ?? [0]);
                    return $aDays <=> $bDays;
                });
                return $this->dataResponse('success', 200, __('msg.record_found'), $collections);
            }
            return $this->dataResponse('error', 200, 'Item Disposition Model' . ' ' . __('msg.record_not_found'));
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
            $productionItems = ProductionItemModel::where('production_batch_id', $id)->first();
            $productionBatch = $productionItems->productionBatch;
            $itemDisposition = ItemDispositionModel::where('production_batch_id', $id)->get();
            if ($productionBatch) {
                DB::beginTransaction();
                $response = null;
                if ($fields['is_release']) {
                    $response = $this->onReleaseHoldStatus($productionItems, $productionBatch, $itemDisposition, $createdById);
                } else {
                    $response = $this->onHoldStatus($productionItems, $productionBatch, $itemDisposition, $createdById);
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

    public function onHoldStatus($productionItems, $productionBatch, $itemDisposition, $createdById)
    {
        try {
            $producedItemArray = json_decode($productionItems->produced_items);
            foreach ($producedItemArray as $key => $value) {
                if ($value->sticker_status === 1) {
                    if ($value->status !== 1) {
                        $value->prev_status = $value->status;
                    }
                    $value->status = 1;
                    $this->createProductionLog(ProductionItemModel::class, $productionItems->id, $value, $createdById, 1, $key);
                }
            }

            foreach ($itemDisposition as $disposition) {
                $disposition->is_release = 0;
                $disposition->save();
                $this->createProductionLog(ItemDispositionModel::class, $disposition->id, $disposition->getAttributes(), $createdById, 1, $disposition->item_key);
            }
            $productionBatch->status = 1;
            $productionBatch->update();
            $this->createProductionLog(ProductionBatchModel::class, $productionBatch->id, $productionBatch->getAttributes(), $createdById, 1);
            $productionItems->produced_items = json_encode($producedItemArray);
            $productionItems->update();
            $this->createProductionLog(ProductionItemModel::class, $productionItems->id, $productionItems->getAttributes(), $createdById, 1);
            $response = [
                'status' => $productionBatch->statusLabel
            ];
            return $response;
        } catch (Exception $exception) {
            DB::rollBack();
            throw new Exception($exception->getMessage());
        }
    }

    public function onReleaseHoldStatus($productionItems, $productionBatch, $itemDisposition, $createdById)
    {
        try {
            $producedItemArray = json_decode($productionItems->produced_items);
            foreach ($producedItemArray as $key => $value) {

                if ($value->sticker_status === 1) {
                    $value->status = $value->prev_status;
                    $this->createProductionLog(ProductionItemModel::class, $productionItems->id, $value, $createdById, 1, $key);
                }
            }
            $productionBatch->status = 0;
            if ($productionBatch->productionOrder->status === 1) {
                $productionBatch->status = 2;
            }
            foreach ($itemDisposition as $disposition) {
                $disposition->is_release = 1;
                $disposition->save();
                $this->createProductionLog(ItemDispositionModel::class, $disposition->id, $disposition->getAttributes(), $createdById, 1, $disposition->item_key);
            }
            $productionBatch->update();
            $this->createProductionLog(ProductionBatchModel::class, $productionBatch->id, $productionBatch->getAttributes(), $createdById, 1);
            $productionItems->produced_items = json_encode($producedItemArray);
            $productionItems->update();
            $this->createProductionLog(ProductionItemModel::class, $productionItems->id, $productionItems->getAttributes(), $createdById, 1);
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

    public function onGetEndorsedByQaItems($item_disposition_id)
    {
        try {
            $itemDispositionModel = ItemDispositionModel::find($item_disposition_id);
            // $productionType = $itemDispositionModel->production_type; // 0 = otb, 1 = ota
            $fulfilledBatch = ProductionBatchModel::find($itemDispositionModel->fulfilled_batch_id);
            $productionItems = json_decode($fulfilledBatch->productionItems->produced_items, true);
            $data = null;
            if ($itemDispositionModel->action == 9) {
                $data = [
                    'produced_items' => json_encode([$itemDispositionModel->item_key => $productionItems[$itemDispositionModel->item_key]]),
                    'production_batch_id' => $itemDispositionModel->production_batch_id,
                    'production_batch' => $itemDispositionModel->productionBatch
                ];
            } else {
                $data = [
                    'produced_items' => json_encode($productionItems),
                    'production_batch' => $fulfilledBatch,
                    'batch_origin' => $itemDispositionModel->production_batch_id,
                ];
            }

            return $this->dataResponse('success', 200, __('msg.record_found'), $data);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onQaItemDispositionRepository($itemDisposition, $quantity, $type, $createdById)
    {
        try {
            $repositoryArray = [0, 1, 2, 3]; // 0 = For Disposal, 1 = For Intersell, 2 = For Store Distribution, 3 = For Complimentary
            if (in_array($type, $repositoryArray) && $quantity > 0) {
                $productionBatchId = $itemDisposition->production_batch_id;
                $itemMasterdataId = $itemDisposition->productionBatch->itemMasterdata->id;
                $itemDispositionRepository = new ItemDispositionRepositoryModel();
                $itemDispositionRepository->item_disposition_id = $itemDisposition->id;
                $itemDispositionRepository->type = $type;
                $itemDispositionRepository->production_batch_id = $productionBatchId;
                $itemDispositionRepository->item_id = $itemMasterdataId;
                $itemDispositionRepository->item_key = $itemDisposition->item_key;
                $itemDispositionRepository->quantity = $quantity;
                $itemDispositionRepository->created_by_id = $createdById;
                $itemDispositionRepository->save();
            }
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onApplyReferenceNumberToExisting()
    {
        try {
            DB::beginTransaction();
            $forInvestigationCollection = ItemDispositionModel::where('type', 0)->whereNull('reference_number')->orderBy('id', 'ASC')->get();
            $investigationReferenceNumbers = [];
            foreach ($forInvestigationCollection as $investigationValue) {
                if (!isset($investigationReferenceNumbers[$investigationValue['production_batch_id']])) {
                    $investigationReferenceNumbers[$investigationValue['production_batch_id']] = [
                        'disposition_ids' => []
                    ];
                }
                $investigationReferenceNumbers[$investigationValue['production_batch_id']]['disposition_ids'][] = $investigationValue['id'];
            }

            $investigationCounter = 1;
            foreach ($investigationReferenceNumbers as $investigationRefValue) {
                $referenceNumber = 'FI-5' . str_pad($investigationCounter, 6, '0', STR_PAD_LEFT);
                foreach ($investigationRefValue['disposition_ids'] as $itemDispositionId) {
                    $itemDispositionModel = ItemDispositionModel::find($itemDispositionId);
                    $itemDispositionModel->reference_number = $referenceNumber;
                    $itemDispositionModel->save();
                }
                $investigationCounter++;
            }

            $forSamplingCollection = ItemDispositionModel::where('type', 1)->whereNull('reference_number')->orderBy('id', 'ASC')->get();
            $forSamplingReferenceNumbers = [];
            foreach ($forSamplingCollection as $samplingValue) {
                if (!isset($forSamplingReferenceNumbers[$samplingValue['production_batch_id']])) {
                    $forSamplingReferenceNumbers[$samplingValue['production_batch_id']] = [
                        'disposition_ids' => []
                    ];
                }
                $forSamplingReferenceNumbers[$samplingValue['production_batch_id']]['disposition_ids'][] = $samplingValue['id'];
            }

            $samplingCounter = 1;
            foreach ($forSamplingReferenceNumbers as $investigationRefValue) {
                $referenceNumber = 'LS-5' . str_pad($samplingCounter, 6, '0', STR_PAD_LEFT);
                foreach ($investigationRefValue['disposition_ids'] as $itemDispositionId) {
                    $itemDispositionModel = ItemDispositionModel::find($itemDispositionId);
                    $itemDispositionModel->reference_number = $referenceNumber;
                    $itemDispositionModel->save();
                }
                $samplingCounter++;
            }
            DB::commit();
            return $this->dataResponse('success', 200, __('msg.update_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
