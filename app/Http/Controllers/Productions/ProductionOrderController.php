<?php

namespace App\Http\Controllers\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProductionOrder;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ProductionOrderController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($orderId = null)
    {
        return [
            'created_by_id' => 'required|exists:credentials,id',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'reference_number' => 'required|string|unique:production_orders,reference_number,' . $orderId,
            'production_date' => 'required|date,format:Y-m-d',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(ProductionOrder::class, $request, $this->getRules(), 'Production Order');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ProductionOrder::class, $request, $this->getRules($id), 'Production Order', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOrder::class, $request, $searchableFields, 'Production Order');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOrder::class, $id, 'Production Order');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ProductionOrder::class, $id, 'Production Order');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProductionOrder::class, $id, 'Production Order');
    }
}
