<?php

namespace App\Http\Controllers\v1\WMS\Settings\ItemMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\ItemMasterData\ItemConversionModel;
use App\Traits\MosCrudOperationsTrait;
use Illuminate\Http\Request;

class ItemConversionController extends Controller
{
    use MosCrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_item_conversions,code,' . $itemId,
            'short_name' => 'required|string|unique:wms_item_conversions,short_name,' . $itemId,
            'long_name' => 'required|string|unique:wms_item_conversions,long_name,' . $itemId,
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemConversionModel::class, $request, $this->getRules(), 'Item Conversion');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemConversionModel::class, $request, $this->getRules($id), 'Item Conversion', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code', 'short_name', 'long_name'];
        return $this->readPaginatedRecord(ItemConversionModel::class, $request, $searchableFields, 'Item Conversion');
    }
    public function onGetall()
    {
        return $this->readRecord(ItemConversionModel::class, 'Item Conversion');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemConversionModel::class, $id, 'Item Conversion');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemConversionModel::class, $id, 'Item Conversion');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemConversionModel::class, $id, 'Item Conversion', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ItemConversionModel::class, 'Item Conversion', $fields);
    }

}
