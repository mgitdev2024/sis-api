<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProductionOTAModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ProductionOTAController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:credentials,id',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'production_order_id' => 'required|exists:production_orders,id',
            'item_code' => 'required|string',
            'production_date' => 'required|date,format:Y-m-d',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(ProductionOTAModel::class, $request, $this->getRules(), 'Production OTA');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ProductionOTAModel::class, $request, $this->getRules(), 'Production OTA', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOTAModel::class, $request, $searchableFields, 'Production OTA');
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
            if (isset ($currentProductionOrder->getOriginalContent()['success'])) {
                $whereFields = [
                    'production_order_id' => $currentProductionOrder->getOriginalContent()['success']['data'][0]['id']
                ];
            }
        }
        return $this->readCurrentRecord(ProductionOTAModel::class, $id, $whereFields, null, 'Production OTA');
    }
}
