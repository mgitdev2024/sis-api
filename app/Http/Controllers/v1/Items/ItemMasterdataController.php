<?php

namespace App\Http\Controllers\v1\Items;

use App\Http\Controllers\Controller;
use App\Models\Items\ItemMasterdata;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ItemMasterdataController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required|exists:credentials,id',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'item_code' => 'required|string|unique:item_masterdata,item_code,' . $itemId,
            'name' => 'required|string',
            'item_classification_id' => 'required|integer|exists:item_classifications,id',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemMasterdata::class, $request, $this->getRules(), 'Item Masterdata');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemMasterdata::class, $request, $this->getRules($id), 'Item Masterdata', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name', 'item_code', 'item_classification_label'];
        return $this->readPaginatedRecord(ItemMasterdata::class, $request, $searchableFields, 'Item Masterdata');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemMasterdata::class, $id, 'Item Masterdata');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemMasterdata::class, $id, 'Item Masterdata');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ItemMasterdata::class, $id, 'Item Masterdata');
    }
}
