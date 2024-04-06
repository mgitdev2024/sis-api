<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProducedItemModel;
use App\Models\Productions\ProductionBatchModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use Exception;
use DB;

class ProducedItemController extends Controller
{
    use CrudOperationsTrait;
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'chilled_exp_date' => 'required|date',
        ];
        return $this->updateRecordById(ProducedItemModel::class, $request, $rules, 'Produced Item', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProducedItemModel::class, $request, $searchableFields, 'Produced Item');
    }
    public function onGetAll()
    {
        return $this->readRecord(ProducedItemModel::class, 'Produced Item');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProducedItemModel::class, $id, 'Produced Item');
    }

    public function onChangeStatus($id, Request $request)
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

        $rules = [
            'scanned_item_qr' => 'required|string',
            'status_id' => 'required|integer|between:0,5',
            'is_deactivate' => 'required|boolean',
            'created_by_id' => 'required'
        ];
        $fields = $request->validate($rules);
        $statusId = $fields['status_id'];
        $createdBy = $fields['created_by_id'];
        return $fields['is_deactivate'] ? $this->onDeactivateItem($id, $fields) : $this->onUpdateItemStatus($statusId, $id, $fields, $createdBy);
    }

    public function onUpdateItemStatus($statusId, $id, $fields, $createdById)
    {
        try {
            DB::beginTransaction();
            $forQaDisposition = [4, 5];
            $scannedItem = json_decode($fields['scanned_item_qr'], true);

            $productionBatch = ProductionBatchModel::find($id);
            $producedItemModel = $productionBatch->producedItem;
            $producedItem = $producedItemModel->produced_items;
            $producedItemArray = json_decode($producedItem, true);

            foreach ($scannedItem as $value) {
                $producedItemArray[$value]['status'] = $statusId;
                if (in_array($statusId, $forQaDisposition)) {
                    $this->onItemDisposition($createdById, $id, $producedItemArray[$value], $statusId);
                }
            }

            $producedItemModel->produced_items = json_encode($producedItemArray);
            $producedItemModel->update();
            DB::commit();
            return $this->dataResponse('success', 201, 'Produced Item ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, 'Produced Item ' . __('msg.update_failed'));
        }
    }

    public function onDeactivateItem($id, $fields)
    {
        try {
            DB::beginTransaction();

            $scannedItem = json_decode($fields['scanned_item_qr'], true);

            $productionBatch = ProductionBatchModel::find($id);
            $producedItemModel = $productionBatch->producedItem;
            $producedItem = $producedItemModel->produced_items;
            $producedItemArray = json_decode($producedItem, true);

            foreach ($scannedItem as $value) {
                $producedItemArray[$value]['sticker_status'] = 0;
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

    public function onItemDisposition($createdById, $id, $value, $statusId)
    {
        try {
            $type = 1;
            if ($statusId == 4) {
                $type = 0;
            }
            $itemDisposition = new ItemDispositionModel();
            $itemDisposition->created_by_id = $createdById;
            $itemDisposition->production_batch_id = $id;
            $itemDisposition->type = $type;
            $itemDisposition->produced_items = json_encode($value);
            $itemDisposition->save();
        } catch (Exception $exception) {
            dd($exception);
            throw new Exception($exception->getMessage());
        }
    }
}
