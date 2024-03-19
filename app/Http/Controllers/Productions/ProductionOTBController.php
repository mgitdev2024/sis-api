<?php

namespace App\Http\Controllers\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProductionOTB;
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
            'production_date' => 'required|date,format:Y-m-d',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(ProductionOTB::class, $request, $this->getRules(), 'Production OTB');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ProductionOTB::class, $request, $this->getRules(), 'Production OTB', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOTB::class, $request, $searchableFields, 'Production OTB');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOTB::class, $id, 'Production OTB');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ProductionOTB::class, $id, 'Production OTB');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProductionOTB::class, $id, 'Production OTB');
    }
}
