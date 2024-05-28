<?php

namespace App\Http\Controllers\v1\WMS\Settings\ItemMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\ItemMasterData\ItemUomModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class ItemUomController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:item_uoms,code,' . $itemId,
            'short_name' => 'required|string|unique:item_uoms,short_name,' . $itemId,
            'long_name' => 'required|string|unique:item_uoms,long_name,' . $itemId,
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemUomModel::class, $request, $this->getRules(), 'Item UOM');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemUomModel::class, $request, $this->getRules($id), 'Item UOM', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code','short_name', 'long_name'];
        return $this->readPaginatedRecord(ItemUomModel::class, $request, $searchableFields, 'Item UOM');
    }
    public function onGetall()
    {
        return $this->readRecord(ItemUomModel::class, 'Item UOM');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemUomModel::class, $id, 'Item UOM');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemUomModel::class, $id, 'Item UOM');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemUomModel::class, $id, 'Item UOM', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ItemUomModel::class, 'Item UOM', $fields);
    }
}
