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
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
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
    public function onGetall()
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
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemClassificationModel::class, $id, 'Item Classification', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ItemClassificationModel::class, 'Item Classification', $fields);
    }
}
