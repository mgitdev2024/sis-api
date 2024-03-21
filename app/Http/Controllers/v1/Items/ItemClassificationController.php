<?php

namespace App\Http\Controllers\v1\Items;

use App\Http\Controllers\Controller;
use App\Models\Items\ItemClassification;
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
        return $this->createRecord(ItemClassification::class, $request, $this->getRules(), 'Item Classification');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemClassification::class, $request, $this->getRules($id), 'Item Classification', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name'];
        return $this->readPaginatedRecord(ItemClassification::class, $request, $searchableFields, 'Item Classification');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemClassification::class, $id, 'Item Classification');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemClassification::class, $id, 'Item Classification');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ItemClassification::class, $id, 'Item Classification');
    }
}
