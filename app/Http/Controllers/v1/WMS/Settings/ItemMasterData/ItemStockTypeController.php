<?php

namespace App\Http\Controllers\v1\WMS\Settings\ItemMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\ItemMasterData\ItemStockTypeModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class ItemStockTypeController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:item_stock_types,code,' . $itemId,
            'short_name' => 'required|string|unique:item_stock_types,short_name,' . $itemId,
            'long_name' => 'required|string|unique:item_stock_types,long_name,' . $itemId,
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemStockTypeModel::class, $request, $this->getRules(), 'Item Stock Type');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemStockTypeModel::class, $request, $this->getRules($id), 'Item Stock Type', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code','short_name', 'long_name'];
        return $this->readPaginatedRecord(ItemStockTypeModel::class, $request, $searchableFields, 'Item Stock Type');
    }
    public function onGetall()
    {
        return $this->readRecord(ItemStockTypeModel::class, 'Item Stock Type');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemStockTypeModel::class, $id, 'Item Stock Type');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemStockTypeModel::class, $id, 'Item Stock Type');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemStockTypeModel::class, $id, 'Item Stock Type', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ItemStockTypeModel::class, 'Item Stock Type', $fields);
    }
}
