<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProductionOTBModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ProductionOTBController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:credentials,id',
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
            'created_by_id' => 'required|exists:credentials,id',
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
            if (isset ($currentProductionOrder->getOriginalContent()['success'])) {
                $whereFields = [
                    'production_order_id' => $currentProductionOrder->getOriginalContent()['success']['data'][0]['id']
                ];
            }
        }
        return $this->readCurrentRecord(ProductionOTBModel::class, $id, $whereFields, null, 'Production OTB');
    }
}
