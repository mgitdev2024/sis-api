<?php

namespace App\Http\Controllers\v1\WMS\Settings\ItemMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\ItemMasterData\ItemMovementModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class ItemMovementController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:item_movements,code,' . $itemId,
            'short_name' => 'required|string|unique:item_movements,short_name,' . $itemId,
            'long_name' => 'required|string|unique:item_movements,long_name,' . $itemId,
            'description' => 'string|nullable',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemMovementModel::class, $request, $this->getRules(), 'Item Movement');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemMovementModel::class, $request, $this->getRules($id), 'Item Movement', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code','short_name', 'long_name'];
        return $this->readPaginatedRecord(ItemMovementModel::class, $request, $searchableFields, 'Item Movement');
    }
    public function onGetall()
    {
        return $this->readRecord(ItemMovementModel::class, 'Item Movement');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemMovementModel::class, $id, 'Item Movement');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemMovementModel::class, $id, 'Item Movement');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemMovementModel::class, $id, 'Item Movement', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ItemMovementModel::class, 'Item Movement', $fields);
    }
}
