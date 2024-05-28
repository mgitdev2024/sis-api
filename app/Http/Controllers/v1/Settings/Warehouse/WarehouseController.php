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
            'updated_by_id' => 'nullable',
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
    public function onGetall()
    {
        return $this->readRecord(WarehouseLocationModel::class, 'Warehouse Location');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(WarehouseLocationModel::class, $id, 'Warehouse Location');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(WarehouseLocationModel::class, $id, 'Warehouse Location');
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(WarehouseLocationModel::class, 'Warehouse Location', $fields);
    }
}
