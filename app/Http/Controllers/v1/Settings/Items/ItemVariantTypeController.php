<?php

namespace App\Http\Controllers\v1\Settings\Items;

use App\Http\Controllers\Controller;
use App\Models\Settings\Items\ItemVariantTypeModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ItemVariantTypeController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'name' => 'required|string|unique:item_variant_types,name,' . $itemId,
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemVariantTypeModel::class, $request, $this->getRules(), 'Item Variant Type');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemVariantTypeModel::class, $request, $this->getRules($id), 'Item Variant Type', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name'];
        return $this->readPaginatedRecord(ItemVariantTypeModel::class, $request, $searchableFields, 'Item Variant Type');
    }
    public function onGetall()
    {
        return $this->readRecord(ItemVariantTypeModel::class, 'Item Variant Type');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemVariantTypeModel::class, $id, 'Item Variant Type');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemVariantTypeModel::class, $id, 'Item Variant Type');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemVariantTypeModel::class, $id, 'Item Variant Type', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(ItemVariantTypeModel::class, 'Item Variant Type', $fields);
    }
}
