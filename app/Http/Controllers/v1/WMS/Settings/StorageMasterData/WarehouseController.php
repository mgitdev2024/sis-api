<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_warehouses,code,' . $itemId,
            'short_name' => 'required|string|unique:wms_storage_warehouses,short_name,' . $itemId,
            'long_name' => 'required|string|unique:wms_storage_warehouses,long_name,' . $itemId,
            'description' => 'string|nullable',
            'facility_id' => 'required|integer|exists:wms_storage_facility_plants,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(WarehouseModel::class, $request, $this->getRules(), 'Warehouse');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(WarehouseModel::class, $request, $this->getRules($id), 'Warehouse', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code','short_name','long_name'];
        return $this->readPaginatedRecord(WarehouseModel::class, $request, $searchableFields, 'Warehouse');
    }
    public function onGetall()
    {
        return $this->readRecord(WarehouseModel::class, 'Warehouse');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(WarehouseModel::class, $id, 'Warehouse');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(WarehouseModel::class, $id, 'Warehouse');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(WarehouseModel::class, $id, 'Warehouse', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(WarehouseModel::class, 'Warehouse', $fields);
    }
}
