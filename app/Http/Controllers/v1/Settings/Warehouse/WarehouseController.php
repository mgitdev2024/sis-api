<?php

namespace App\Http\Controllers\v1\Settings\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Settings\WarehouseLocationModel;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use CrudOperationsTrait;
    use ResponseTrait;
    public static function getRules()
    {
        return [
            // |exists:personal_informations,id
            'created_by_id' => 'required',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'status' => 'nullable|integer',

        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(WarehouseLocationModel::class, $request, $this->getRules(), 'Warehouse Location');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(WarehouseLocationModel::class, $request, $this->getRules(), 'Warehouse Location', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name', 'description'];
        return $this->readPaginatedRecord(WarehouseLocationModel::class, $request, $searchableFields, 'Warehouse Location');
    }
    public function onGetall(Request $request)
    {
        return $this->readRecord(WarehouseLocationModel::class, $request, 'Warehouse Location');
    }
    public function onGetById($id, Request $request)
    {
        return $this->readRecordById(WarehouseLocationModel::class, $id, $request, 'Warehouse Location');
    }
    public function onDeleteById($id, Request $request)
    {
        return $this->deleteRecordById(WarehouseLocationModel::class, $id, $request, 'Warehouse Location');
    }
}
