<?php

namespace App\Http\Controllers\v1\Settings\Items;

use App\Http\Controllers\Controller;
use App\Models\Settings\Items\ItemCategoryModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ItemCategoryController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'name' => 'required|string|unique:item_categories,name,' . $itemId,
            'code' => 'required|string|unique:item_categories,code,' . $itemId,
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemCategoryModel::class, $request, $this->getRules(), 'Item Category');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemCategoryModel::class, $request, $this->getRules($id), 'Item Category', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name'];
        return $this->readPaginatedRecord(ItemCategoryModel::class, $request, $searchableFields, 'Item Category');
    }
    public function onGetall()
    {
        return $this->readRecord(ItemCategoryModel::class, 'Item Category');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemCategoryModel::class, $id, 'Item Category');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemCategoryModel::class, $id, 'Item Category');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemCategoryModel::class, $id, 'Item Category', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ItemCategoryModel::class, 'Item Category', $fields);
    }
}
