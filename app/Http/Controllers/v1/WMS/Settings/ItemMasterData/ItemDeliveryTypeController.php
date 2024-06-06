<?php

namespace App\Http\Controllers\v1\WMS\Settings\ItemMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\ItemMasterData\ItemDeliveryTypeModel;
use App\Traits\MosCrudOperationsTrait;
use Illuminate\Http\Request;

class ItemDeliveryTypeController extends Controller
{
    use MosCrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'name' => 'required|string|unique:wms_item_delivery_types,name,' . $itemId,
            'code' => 'required|string|unique:wms_item_delivery_types,code,' . $itemId,
            'description' => 'string|nullable',

        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemDeliveryTypeModel::class, $request, $this->getRules(), 'Item Delivery Type');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemDeliveryTypeModel::class, $request, $this->getRules($id), 'Item Delivery Type', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name', 'code'];
        return $this->readPaginatedRecord(ItemDeliveryTypeModel::class, $request, $searchableFields, 'Item Delivery Type');
    }
    public function onGetall()
    {
        return $this->readRecord(ItemDeliveryTypeModel::class, 'Item Delivery Type');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemDeliveryTypeModel::class, $id, 'Item Delivery Type');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemDeliveryTypeModel::class, $id, 'Item Delivery Type');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemDeliveryTypeModel::class, $id, 'Item Delivery Type', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ItemDeliveryTypeModel::class, 'Item Delivery Type', $fields);
    }
}
