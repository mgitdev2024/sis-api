<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ArchivedBatchesModel;
use App\Models\Productions\ProducedItemModel;
use App\Models\Productions\ProductionBatchModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use DB;
use Exception;

class ArchivedBatchesController extends Controller
{
    use CrudOperationsTrait;
    public function onGetCurrent()
    {
        $orderFields = [
            'created_at' => 'ASC'
        ];
        return $this->readCurrentRecord(ArchivedBatchesModel::class, null, null, null, $orderFields, 'Archived Batches');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ArchivedBatchesModel::class, $id, 'Archived Batches');
    }
    public function onArchiveBatch(Request $request, $id)
    {
        
        $fields = $request->validate([
            'created_by_id' => 'required',
            'reason' => 'required',
            'attachment' => 'nullable',
        ]);
        try {
            DB::beginTransaction();
            $productionBatch = ProductionBatchModel::find($id);
            $producedItems = $productionBatch->producedItem;
            $record = new ArchivedBatchesModel();
            $record->fill($fields);
            $record->production_order_id = $productionBatch->productionOrder->id;
            $record->batch_number = $productionBatch->batch_number;
            $record->production_type = $productionBatch->production_ota_id != null ? 1 : 0;
            $record->production_batch_data = json_encode($productionBatch);
            $record->produced_items_data = json_encode($producedItems);
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('public/attachments/archived-batch');
                $filepath = 'storage/' . substr($attachmentPath, 7);
                $record->attachment = $filepath;
            }
            $record->save();
            $this->createProductionHistoricalLog(ProductionBatchModel::class, $productionBatch->id, $productionBatch, $fields['created_by_id'], 2);
            $this->createProductionHistoricalLog(ProducedItemModel::class, $producedItems->id, $producedItems, $fields['created_by_id'], 2);
            $producedItems->delete();
            $productionBatch->delete();
            DB::commit();
            return $this->dataResponse('success', 201, 'Production Batch ' . __('msg.delete_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.delete_failed'));
        }
    }
}
