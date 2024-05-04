<?php

namespace App\Http\Controllers\v1\Settings\Category;

use App\Http\Controllers\Controller;
use App\Models\Settings\SubCategoryModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;
class SubCategoryController extends Controller
{
    use CrudOperationsTrait;
    use ResponseTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required',
            'category_id' => 'required|exists:categories,id',
            'sub_category_code' => 'required|string|max:255',
            'sub_category_name' => 'required|string|max:255',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(SubCategoryModel::class, $request, $this->getRules(), 'Sub Category');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(SubCategoryModel::class, $request, $this->getRules(), 'Sub Category', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['sub_category_code','sub_category_name'];
        return $this->readPaginatedRecord(SubCategoryModel::class, $request, $searchableFields, 'Sub Category');
    }
    public function onGetall(Request $request)
    {
        return $this->readRecord(SubCategoryModel::class, $request,'Sub Category');
    }
    public function onGetById($id,Request $request)
    {
        return $this->readRecordById(SubCategoryModel::class, $id, $request,'Sub Category');
    }
    public function onDeleteById($id,Request $request)
    {
        return $this->deleteRecordById(SubCategoryModel::class, $id, $request,'Sub Category');
    }
    public function onGetChildByParentId($id,Request $request)
    {
        return $this->readRecordByParentId(SubCategoryModel::class, $id, $request,'Sub Category', 'category_id');
    }
    public function onGetPaginatedChildByParentId(Request $request, $id)
    {
        $searchableFields = ['name'];
        return $this->readPaginatedRecordByID(SubCategoryModel::class, $request, $searchableFields, 'Sub Category', 'category_id', $id);
    }
}
