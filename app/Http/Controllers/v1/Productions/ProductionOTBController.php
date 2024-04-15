<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
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
    public function onGetAll()
    {
        return $this->readRecord(ProductionOTBModel::class, 'Production OTB');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOTBModel::class, $id, 'Production OTB');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ProductionOTBModel::class, $id, 'Production OTB');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProductionOTBModel::class, $id, 'Production OTB');
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
        return $this->readCurrentRecord(ProductionOTBModel::class, $id, $whereFields, null, null, 'Production OTB');
    }
    public function onGetEndorsedByQa($id = null)
    {
        try {
            $itemDisposition = ItemDispositionModel::with('productionBatch')->where('production_type', 0)->where('production_status', 1);

            if ($id != null) {
                $itemDisposition->where('id', $id);
            }
            $result = $itemDisposition->get();
            return $this->dataResponse('success', 200, $result);
        } catch (\Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onFulfillEndorsement(Request $request, $id)
    {
        $fields = $request->validate([
            'created_by_id' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $itemDisposition = ItemDispositionModel::find($id);
            $itemDisposition->fulfilled_by_id = $fields['created_by_id'];
            $itemDisposition->fulfilled_at = now();
            $itemDisposition->production_status = 0;
            $itemDisposition->save();

            $producedItemModel = ProducedItemModel::where('production_batch_id', $itemDisposition->production_batch_id)->first();
            $producedItems = json_decode($producedItemModel->produced_items, true);
            $producedItems[$itemDisposition->item_key]['status'] = 9;
            $producedItemModel->produced_items = json_encode($producedItems);
            $producedItemModel->save();
            DB::commit();
            return $this->dataResponse('success', 200, ProductionOTBModel::class . ' ' . __('msg.update_success'));
        } catch (\Exception $exception) {
            DB::rollback();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }

    }
}
