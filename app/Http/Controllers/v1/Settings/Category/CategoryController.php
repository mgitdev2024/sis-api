<?php

namespace App\Http\Controllers\v1\Settings\Category;

use App\Http\Controllers\Controller;
use App\Models\Settings\CategoryModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use App\Traits\ResponseTrait;

class CategoryController extends Controller
{
    use CrudOperationsTrait;
    use ResponseTrait;
    public static function getRules()
    {
        return [
            // |exists:personal_informations,id
            'created_by_id' => 'required',
            'category_name' => 'nullable|string|max:255',
            'category_code' => 'required|string|max:255',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(CategoryModel::class, $request, $this->getRules(), 'Category');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(CategoryModel::class, $request, $this->getRules(), 'Category', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['category_code', 'category_name'];
        return $this->readPaginatedRecord(CategoryModel::class, $request, $searchableFields, 'Category');
    }
    public function onGetAll(Request $request)
    {
        return $this->readRecord(CategoryModel::class, $request,'Category');
    }
    public function onGetById(Request $request,$id)
    {
        return $this->readRecordById(CategoryModel::class, $id, $request,'Category');
    }
    public function onDeleteById(Request $request,$id)
    {
        return $this->deleteRecordById(CategoryModel::class, $id, $request,'Category');
    }
}
