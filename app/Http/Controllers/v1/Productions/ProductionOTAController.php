<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProducedItemModel;
use App\Models\Productions\ProductionOTAModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use Exception;
use DB;
use App\Traits\CrudOperationsTrait;

class ProductionOTAController extends Controller
{
    use CrudOperationsTrait;
    use ResponseTrait;
    public static function onGetRules()
    {
        return [
            'production_batch_id' => 'nullable|integer|exists:production_batch,id',
            'production_ota_id' => 'required|exists:production_ota,id',
            'batch_type' => 'required|integer|in:0,1',
            'quantity' => 'required',
            'chilled_exp_date' => 'nullable|date',
            'created_by_id' => 'required',
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->onGetRules());
        try {
            $batch = null;
            DB::beginTransaction();
            if (isset($fields['production_batch_id'])) {
                $batch = $this->onAddToExistingBatch($fields);
            } else {
                $batch = $this->onInitialBatch($fields);
            }

            DB::commit();
            return $this->dataResponse('success', 201, 'Production Batch ' . __('msg.create_success'), $batch);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'plotted_quantity' => 'required|integer',
            'actual_quantity' => 'nullable|integer',
        ];
        return $this->updateRecordById(ProductionOTAModel::class, $request, $rules, 'Production OTA', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOTAModel::class, $request, $searchableFields, 'Production OTA');
    }
    public function onGetAll()
    {
        return $this->readRecord(ProductionOTAModel::class, 'Production OTA');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOTAModel::class, $id, 'Production OTA');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ProductionOTAModel::class, $id, 'Production OTA');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProductionOTAModel::class, $id, 'Production OTA');
    }
    public function onGetCurrent($id = null)
    {
        $whereFields = [];
        if ($id != null) {
            $whereFields = [
                'production_order_id' => $id
            ];
        } else {
            $productionOrder = new ProductionOrderController();
            $currentProductionOrder = $productionOrder->onGetCurrent();

            $whereFields = [];
            if (isset($currentProductionOrder->getOriginalContent()['success'])) {
                $whereFields = [
                    'production_order_id' => $currentProductionOrder->getOriginalContent()['success']['data'][0]['id']
                ];
            }
        }
        return $this->readCurrentRecord(ProductionOTAModel::class, $id, $whereFields, null, null, 'Production OTA');
    }
    public function onGetEndorsedByQa($id = null)
    {
        try {
            $itemDisposition = ItemDispositionModel::with('productionBatch')->where('production_type', 1)->where('production_status', 1);

            if ($id != null) {
                $itemDisposition->where('id', $id);
            }
            $result = $itemDisposition->get();
            return $this->dataResponse('success', 200, $result);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onFulfillEndorsement(Request $request, $id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'chilled_exp_date' => 'nullable|date',
            'frozen_exp_date' => 'nullable|date',
        ]);
        try {
            $itemStatus = [
                '6' => 11,
                '7' => 12,
            ];
            DB::beginTransaction();
            $itemDisposition = ItemDispositionModel::find($id);
            $itemDisposition->fulfilled_by_id = $fields['created_by_id'];
            $itemDisposition->fulfilled_at = now();
            $itemDisposition->production_status = 0;
            $itemDisposition->save();

            $producedItemModel = ProducedItemModel::where('production_batch_id', $itemDisposition->production_batch_id)->first();
            $producedItems = json_decode($producedItemModel->produced_items, true);
            $producedItems[$itemDisposition->item_key]['sticker_status'] = 0;
            $producedItems[$itemDisposition->item_key]['status'] = $itemStatus[$producedItems[$itemDisposition->item_key]['status']];
            if (isset($fields['chilled_exp_date'])) {
                $producedItems[$itemDisposition->item_key]['new_chilled_exp_date'] = $fields['chilled_exp_date'];
            }
            if (isset($fields['frozen_exp_date'])) {
                $producedItems[$itemDisposition->item_key]['new_frozen_exp_date'] = $fields['frozen_exp_date'];
            }

            $producedItemModel->produced_items = json_encode($producedItems);
            $producedItemModel->save();
            DB::commit();
            return $this->dataResponse('success', 200, ProductionOTAModel::class . ' ' . __('msg.update_success'));
        } catch (Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
}
