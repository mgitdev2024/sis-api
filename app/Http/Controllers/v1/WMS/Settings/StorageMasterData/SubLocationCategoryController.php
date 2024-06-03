<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\SubLocationCategoryModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class SubLocationCategoryController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_types,code,' . $itemId,
            'number' => 'integer',
            'has_layer' => 'integer|nullable',
            'layers' => 'string|nullable',
            'sub_location_id' => 'required|integer|exists:wms_storage_sub_locations,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(SubLocationCategoryModel::class, $request, $this->getRules(), 'Sub Location Category');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(SubLocationCategoryModel::class, $request, $this->getRules($id), 'Sub Location Category', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code','short_name','long_name'];
        return $this->readPaginatedRecord(SubLocationCategoryModel::class, $request, $searchableFields, 'Sub Location Category');
    }
    public function onGetall()
    {
        return $this->readRecord(SubLocationCategoryModel::class, 'Sub Location Category');
    }
    public function onGetChildByParentId($id = null)
    {
        return $this->readRecordByParentId(SubLocationCategoryModel::class, 'Sub Location', 'sub_location_id', $id);
    }
    public function onGetById($id)
    {
        return $this->readRecordById(SubLocationCategoryModel::class, $id, 'Sub Location Category');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(SubLocationCategoryModel::class, $id, 'Sub Location Category');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(SubLocationCategoryModel::class, $id, 'Sub Location Category', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(SubLocationCategoryModel::class, 'Sub Location Category', $fields);
    }
}
