<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class SubLocationController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_zones,code,' . $itemId,
            'short_name' => 'required|string|unique:wms_storage_zones,short_name,' . $itemId,
            'long_name' => 'required|string|unique:wms_storage_zones,long_name,' . $itemId,
            'qty' => 'integer|nullable',
            'facility_id' => 'required|integer|exists:wms_storage_facility_plants,id',
            'warehouse_id' => 'required|integer|exists:wms_storage_warehouses,id',
            'zone_id' => 'required|integer|exists:wms_storage_zones,id',

        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(SubLocationModel::class, $request, $this->getRules(), 'Sub Location');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(SubLocationModel::class, $request, $this->getRules($id), 'Sub Location', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code','short_name','long_name'];
        return $this->readPaginatedRecord(SubLocationModel::class, $request, $searchableFields, 'Sub Location');
    }
    public function onGetall()
    {
        return $this->readRecord(SubLocationModel::class, 'Sub Location');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(SubLocationModel::class, $id, 'Sub Location');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(SubLocationModel::class, $id, 'Sub Location');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(SubLocationModel::class, $id, 'Sub Location', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(SubLocationModel::class, 'Sub Location', $fields);
    }
}
