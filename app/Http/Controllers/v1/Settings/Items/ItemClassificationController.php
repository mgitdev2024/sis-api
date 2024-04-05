<?php

namespace App\Http\Controllers\v1\Settings\Items;

use App\Http\Controllers\Controller;
use App\Models\Settings\Items\ItemClassificationModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ItemClassificationController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required|exists:credentials,id',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'name' => 'required|string|unique:item_classifications,name,' . $itemId,
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemClassificationModel::class, $request, $this->getRules(), 'Item Classification');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemClassificationModel::class, $request, $this->getRules($id), 'Item Classification', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name'];
        return $this->readPaginatedRecord(ItemClassificationModel::class, $request, $searchableFields, 'Item Classification');
    }
    public function onGetAll()
    {
        return $this->readRecord(ItemClassificationModel::class, 'Item Classification');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemClassificationModel::class, $id, 'Item Classification');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemClassificationModel::class, $id, 'Item Classification');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ItemClassificationModel::class, $id, 'Item Classification');
    }
}
