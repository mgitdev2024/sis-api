<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class ZoneController extends Controller
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
            'description' => 'string|nullable',
            'facility_id' => 'required|integer|exists:wms_storage_facility_plants,id',
            'warehouse_id' => 'required|integer|exists:wms_storage_warehouses,id',
            'storage_type_id' => 'required|integer|exists:wms_storage_types,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ZoneModel::class, $request, $this->getRules(), 'Zone');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ZoneModel::class, $request, $this->getRules($id), 'Zone', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code','short_name','long_name'];
        return $this->readPaginatedRecord(ZoneModel::class, $request, $searchableFields, 'Zone');
    }
    public function onGetall()
    {
        return $this->readRecord(ZoneModel::class, 'Zone');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ZoneModel::class, $id, 'Zone');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ZoneModel::class, $id, 'Zone');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ZoneModel::class, $id, 'Zone', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ZoneModel::class, 'Zone', $fields);
    }
}
