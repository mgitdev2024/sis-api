<?php

namespace App\Http\Controllers\v1\WMS\Settings\ItemMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\ItemMasterData\ItemClassificationModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;

class ItemClassificationController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_item_classifications,code,' . $itemId,
            'short_name' => 'required|string|unique:wms_item_classifications,short_name,' . $itemId,
            'long_name' => 'nullable|string|unique:wms_item_classifications,long_name,' . $itemId,
            'description' => 'string|nullable',
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
        $searchableFields = ['code','short_name','long_name'];
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
