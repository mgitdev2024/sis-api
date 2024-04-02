<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProductionOTAModel;
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
            'created_by_id' => 'required|exists:credentials,id',
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
            'created_by_id' => 'required|exists:credentials,id',
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
}
