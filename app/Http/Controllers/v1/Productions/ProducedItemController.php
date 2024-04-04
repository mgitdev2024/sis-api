<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProducedItemModel;
use App\Models\Productions\ProductionBatchModel;
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

    public function onChangeStatus($status_id, $id, Request $request)
    {
        // 1 => 'Good',
        // 2 => 'For Investigation',
        // 3 => 'For Sampling',
        // 4 => 'For Disposal',
        // 5 => 'On Hold',
        // 6 => 'For Receive',
        // 7 => 'Received',
        // 8 => 'Deactivate'
        $rules = [
            'scanned_item_qr' => 'required|string',
        ];
        $fields = $request->validate($rules);

        switch ($status_id) {
            case 1:
                $label = 'Good';
                return '';
            case 2:
                $label = 'For Investigation';
                return '';
            case 3:
                $label = 'For Sampling';
                return '';
            case 4:
                $label = 'For Disposal';
                return '';
            case 5:
                $label = 'On Hold';
                return '';
            case 6:
                $label = 'For Receive';
                return $this->onForReceiveItem($id, $fields);
            case 7:
                $label = 'Received';
                return '';
            case 8:
                return $this->onDeactivateItem($id, $fields);
            default:
                return $this->dataResponse('error', 400, 'Produced Item ' . __('msg.update_failed'));
        }
    }

    public function onForReceiveItem($id, $fields)
    {
        try {
            DB::beginTransaction();

            $scannedItem = json_decode($fields['scanned_item_qr'], true);

            $productionBatch = ProductionBatchModel::find($id);
            $producedItemModel = $productionBatch->producedItem;
            $producedItem = $producedItemModel->produced_items;
            $producedItemArray = json_decode($producedItem, true);

            foreach ($scannedItem as $value) {
                $producedItemArray[$value]['status'] = 6;
            }

            $producedItemModel->produced_items = json_encode($producedItemArray);
            $producedItemModel->save();
            // $forCompletionBatch = json_decode($producedItemModel->produced_items, true);
            // $isForReceiveAll = true;

            // foreach ($forCompletionBatch as $item) {
            //     if ($item['status'] !== 6) {
            //         $isForReceiveAll = false;
            //         break;
            //     }
            // }

            // if ($isForReceiveAll) {
            //     $productionBatch->status = 2;
            //     $productionBatch->save();
            // }
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
}
