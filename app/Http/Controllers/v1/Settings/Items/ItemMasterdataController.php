<?php

namespace App\Http\Controllers\v1\Settings\Items;

use App\Http\Controllers\Controller;
use App\Models\Settings\Items\ItemMasterdataModel;
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
            'description' => 'required|string',
            'shelf_life' => 'nullable|integer',
            'item_classification_id' => 'required|integer|exists:item_classifications,id',
            'item_variant_type_id' => 'required|integer|exists:item_variant_types,id',
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemMasterdataModel::class, $request, $this->getRules(), 'Item Masterdata');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemMasterdataModel::class, $request, $this->getRules($id), 'Item Masterdata', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name', 'item_code'];
        return $this->readPaginatedRecord(ItemMasterdataModel::class, $request, $searchableFields, 'Item Masterdata');
    }
    public function onGetAll()
    {
        return $this->readRecord(ItemMasterdata::class, 'Item Masterdata');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemMasterdataModel::class, $id, 'Item Masterdata');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemMasterdataModel::class, $id, 'Item Masterdata');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ItemMasterdataModel::class, $id, 'Item Masterdata');
    }
}
