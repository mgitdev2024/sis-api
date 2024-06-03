<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class StorageTypeController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_types,code,' . $itemId,
            'short_name' => 'required|string|unique:wms_storage_types,short_name,' . $itemId,
            'long_name' => 'required|string|unique:wms_storage_types,long_name,' . $itemId,
            'description' => 'string|nullable'
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(StorageTypeModel::class, $request, $this->getRules(), 'Storage Type');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(StorageTypeModel::class, $request, $this->getRules($id), 'Storage Type', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code','short_name','long_name'];
        return $this->readPaginatedRecord(StorageTypeModel::class, $request, $searchableFields, 'Storage Type');
    }
    public function onGetall()
    {
        return $this->readRecord(StorageTypeModel::class, 'Storage Type');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(StorageTypeModel::class, $id, 'Storage Type');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(StorageTypeModel::class, $id, 'Storage Type');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(StorageTypeModel::class, $id, 'Storage Type', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(StorageTypeModel::class, 'Storage Type', $fields);
    }
}
