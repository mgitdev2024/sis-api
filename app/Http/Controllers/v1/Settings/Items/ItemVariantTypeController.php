<?php

namespace App\Http\Controllers\v1\Settings\Items;

use App\Http\Controllers\Controller;
use App\Models\Items\ItemVariantType;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ItemVariantTypeController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required|exists:credentials,id',
            'updated_by_id' => 'nullable|exists:credentials,id',
            'name' => 'required|string|unique:item_variant_types,name,' . $itemId,
        ];
    }

    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemVariantType::class, $request, $this->getRules(), 'Item Variant Type');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ItemVariantType::class, $request, $this->getRules($id), 'Item Variant Type', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['name'];
        return $this->readPaginatedRecord(ItemVariantType::class, $request, $searchableFields, 'Item Variant Type');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemVariantType::class, $id, 'Item Variant Type');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemVariantType::class, $id, 'Item Variant Type');
    }
    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ItemVariantType::class, $id, 'Item Variant Type');
    }
}
