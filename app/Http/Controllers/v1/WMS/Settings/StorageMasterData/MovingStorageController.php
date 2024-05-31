<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\MovingStorageModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class MovingStorageController extends Controller
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
            'qty' => 'integer|nullable',
            'facility_id' => 'required|integer|exists:wms_storage_facility_plants,id',
            'warehouse_id' => 'required|integer|exists:wms_storage_warehouses,id',
            'zone_id' => 'required|integer|exists:wms_storage_zones,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(MovingStorageModel::class, $request, $this->getRules(), 'Moving Storage');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(MovingStorageModel::class, $request, $this->getRules($id), 'Moving Storage', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code','short_name','long_name'];
        return $this->readPaginatedRecord(MovingStorageModel::class, $request, $searchableFields, 'Moving Storage');
    }
    public function onGetall()
    {
        return $this->readRecord(MovingStorageModel::class, 'Moving Storage');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(MovingStorageModel::class, $id, 'Moving Storage');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(MovingStorageModel::class, $id, 'Moving Storage');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(MovingStorageModel::class, $id, 'Moving Storage', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(MovingStorageModel::class, 'Moving Storage', $fields);
    }
}
