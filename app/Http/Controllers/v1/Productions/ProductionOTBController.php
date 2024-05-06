<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\v1\History\PrintHistoryController;
use App\Models\Productions\ProducedItemModel;
use App\Models\Productions\ProductionOTBModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use DB;

class ProductionOTBController extends Controller
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

    public function onCreate(Request $request)
    {
        return $this->createRecord(ProductionOTBModel::class, $request, $this->getRules(), 'Production OTB');
    }
    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'plotted_quantity' => 'required|integer',
            'actual_quantity' => 'nullable|integer',
        ];
        return $this->updateRecordById(ProductionOTBModel::class, $request, $rules, 'Production OTB', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOTBModel::class, $request, $searchableFields, 'Production OTB');
    }
    public function onGetAll(Request $request)
    {
        return $this->readRecord(ProductionOTBModel::class, $request, 'Production OTB');
    }
    public function onGetById(Request $request,$id)
    {
        return $this->readRecordById(ProductionOTBModel::class, $id, $request, 'Production OTB');
    }
    public function onDeleteById(Request $request,$id)
    {
        return $this->deleteRecordById(ProductionOTBModel::class, $id, $request, 'Production OTB');
    }
    public function onChangeStatus(Request $request,$id)
    {
        return $this->changeStatusRecordById(ProductionOTBModel::class, $id, $request, 'Production OTB');
    }
    public function onGetCurrent(Request $request,$id = null, )
    {
        
        $whereFields = [];
        if ($id != null) {
            $whereFields = [
                'production_order_id' => $id
            ];
        } else {
            $productionOrder = new ProductionOrderController();
            $currentProductionOrder = $productionOrder->onGetCurrent($request);

            $whereFields = [];
            if (isset($currentProductionOrder->getOriginalContent()['success'])) {
                $whereFields = [
                    'production_order_id' => $currentProductionOrder->getOriginalContent()['success']['data'][0]['id']
                ];
            }
        }
        return $this->readCurrentRecord(ProductionOTBModel::class, $id, $whereFields, null, null, $request, 'Production OTB');
    }
    public function onGetEndorsedByQa(Request $request,$id = null)
    {
        $token = $request->bearerToken();
        $this->authenticateToken($token);
        try {
            $itemDisposition = ItemDispositionModel::with('productionBatch')
                ->where('production_type', 0)
                ->where('production_status', 1)
                ->whereNotNull('action');

            if ($id != null) {
                $itemDisposition->where('id', $id);
            }
            $result = $itemDisposition->get();
            return $this->dataResponse('success', 200, __('msg.record_found'), $result);
        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onFulfillEndorsement(Request $request, $id)
    {
        $token = $request->bearerToken();
        $this->authenticateToken($token);
        $fields = $request->validate([
            'created_by_id' => 'required',
            'chilled_exp_date' => 'nullable|date',
            'frozen_exp_date' => 'nullable|date',
        ]);
        try {
            DB::beginTransaction();
            $itemDisposition = ItemDispositionModel::where('id', $id)->where('production_type', 0)->where('production_status', 1)->first();
            if ($itemDisposition) {
                $itemDisposition->fulfilled_by_id = $fields['created_by_id'];
                $itemDisposition->fulfilled_at = now();
                $itemDisposition->production_status = 0;
                $itemDisposition->save();

                $producedItemModel = ProducedItemModel::where('production_batch_id', $itemDisposition->production_batch_id)->first();
                $producedItems = json_decode($producedItemModel->produced_items, true);
                $producedItems[$itemDisposition->item_key]['status'] = 9;
                if (isset($fields['chilled_exp_date'])) {
                    $producedItems[$itemDisposition->item_key]['new_chilled_exp_date'] = $fields['chilled_exp_date'];
                }
                if (isset($fields['frozen_exp_date'])) {
                    $producedItems[$itemDisposition->item_key]['new_frozen_exp_date'] = $fields['frozen_exp_date'];
                }

                $producedItemModel->produced_items = json_encode($producedItems);
                $producedItemModel->save();

                $produceItem = [$itemDisposition->item_key];
                $printHistory = new PrintHistoryController();
                $printHistoryRequest = new Request([
                    'production_batch_id' => $itemDisposition->production_batch_id,
                    'produced_items' => json_encode($produceItem),
                    'is_reprint' => 0,
                    'created_by_id' => $fields['created_by_id'],
                    'item_disposition_id' => $id ?? null
                ]);
                $printHistory->onCreate($printHistoryRequest);

                $data = [
                    'produced_items' => json_encode([$itemDisposition->item_key => $producedItems[$itemDisposition->item_key]]),
                    'production_batch_id' => $itemDisposition->production_batch_id
                ];
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $data);
            }
            return $this->dataResponse('success', 200, __('msg.record_not_found'));
        } catch (\Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }

    }
}
