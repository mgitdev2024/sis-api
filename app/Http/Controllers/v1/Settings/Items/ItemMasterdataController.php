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
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'item_code' => 'required|string|unique:item_masterdata,item_code,' . $itemId,
            'description' => 'required|string',
            'chilled_shelf_life' => 'nullable|integer',
            'category_id' => 'required|integer|exists:categories,id',
            'sub_category_id' => 'required|integer|exists:sub_categories,id',
            'item_classification_id' => 'required|integer|exists:item_classifications,id',
            'item_variant_type_id' => 'required|integer|exists:item_variant_types,id',
            'parent_item_id' => 'required|integer|exists:item_masterdata,id',
            'uom_id' => 'required|integer|exists:uom,id',
            'primary_item_packing_size' => 'required|integer',
            'primary_conversion_id' => 'required|integer|exists:conversions,id',
            'secondary_item_packing_size' => 'required|integer',
            'secondary_conversion_id' => 'required|integer|exists:conversions,id',
            'plant_id' => 'required|integer|exists:plants,id',
            'storage_type_id' => 'required|integer|exists:storage_type,id',
            'stock_type_id' => 'required|integer|exists:stock_type,id',
            'item_movement_id' => 'required|integer|exists:item_movement,id',
            'delivery_lead_time' => 'required|integer',
            're_order_level' => 'required|integer',
            'stock_rotation_type' => 'required|integer',
            'qty_per_pallet' => 'required|integer',
            'dimension' => 'nullable|string',
            'is_qa_required' => 'required|integer',
            'is_qa_disposal' => 'required|integer',
            'image' => 'nullable|string',
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
    public function onGetAll(Request $request)
    {
        return $this->readRecord(ItemMasterdataModel::class, $request, 'Item Masterdata');
    }
    public function onGetById(Request $request, $id)
    {
        return $this->readRecordById(ItemMasterdataModel::class, $id, $request, 'Item Masterdata');
    }
    public function onDeleteById(Request $request, $id)
    {
        return $this->deleteRecordById(ItemMasterdataModel::class, $id, $request, 'Item Masterdata');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemMasterdataModel::class, $id, $request, 'Item Masterdata');
    }
    public function onGetCurrent($id = null, Request $request)
    {
        $whereFields = [
            'item_code' => $id
        ];
        return $this->readCurrentRecord(ItemMasterdataModel::class, $id, $whereFields, null, null, $request, 'Item Masterdata');
    }
}
