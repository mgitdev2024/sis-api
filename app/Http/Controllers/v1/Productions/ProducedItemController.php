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
            'expiration_date' => 'required|date',
        ];
        return $this->updateRecordById(ProducedItemModel::class, $request, $rules, 'Produced Item', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProducedItemModel::class, $request, $searchableFields, 'Produced Item');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProducedItemModel::class, $id, 'Produced Item');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProducedItemModel::class, $id, 'Produced Item');
    }

    public function onDeactivateItem(Request $request)
    {
        $rules = [
            'produced_item_qr' => 'required|string',
        ];
        $fields = $request->validate($rules);
        try {
            DB::beginTransaction();
            $scannedItem = json_decode($fields['produced_item_qr'], true);
            $itemKey = array_keys($scannedItem)[0];

            $batchId = $scannedItem[$itemKey]['bid'];

            $producedItemModel = ProductionBatchModel::find($batchId)->producedItem;
            $producedItem = $producedItemModel->produced_items;
            $producedItemArray = json_decode($producedItem, true);
            $producedItemArray[$itemKey]['status'] = 0;
            $producedItemModel->produced_items = json_encode($producedItemArray);
            $producedItemModel->save();
            DB::commit();
            return $this->dataResponse('success', 201, 'Produced Item ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }


}
