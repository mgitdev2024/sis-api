<?php

namespace App\Http\Controllers\v1\Productions;

use App\Http\Controllers\Controller;
use App\Models\Productions\ProductionOTA;
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
        return $this->createRecord(ProductionOTA::class, $request, $this->getRules(), 'Production OTA');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ProductionOTA::class, $request, $this->getRules(), 'Production OTA', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['reference_number', 'production_date'];
        return $this->readPaginatedRecord(ProductionOTA::class, $request, $searchableFields, 'Production OTA');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ProductionOTA::class, $id, 'Production OTA');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ProductionOTA::class, $id, 'Production OTA');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ProductionOTA::class, $id, 'Production OTA');
    }
}
