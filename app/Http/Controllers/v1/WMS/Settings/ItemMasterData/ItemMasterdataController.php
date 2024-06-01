<?php

namespace App\Http\Controllers\v1\WMS\Settings\ItemMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\ItemMasterData\ItemClassificationModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\ItemMasterData\ItemCategoryModel;
use App\Models\WMS\Settings\ItemMasterData\ItemConversionModel;
use App\Models\WMS\Settings\ItemMasterData\ItemUomModel;
use App\Models\WMS\Settings\ItemMasterData\ItemVariantTypeModel;
use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use DB;
use Exception;

class ItemMasterdataController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($itemId = null)
    {

        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'item_code' => 'required|string|unique:wms_item_masterdata,item_code,' . $itemId,
            'description' => 'required|string',
            'short_name' => 'required|string',
            'chilled_shelf_life' => 'nullable|integer',
            'category_id' => 'required|integer|exists:categories,id',
            'sub_category_id' => 'required|integer|exists:sub_categories,id',
            'item_classification_id' => 'required|integer|exists:item_category,id',
            'item_variant_type_id' => 'required|integer|exists:item_variant_types,id',
            'parent_item_id' => 'required|integer|exists:wms_item_masterdata,id',
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
            'attachment' => 'nullable|string',
            'sticker_remarks' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemMasterdataModel::class, $request, $this->getRules(), 'Item Masterdata', 'public/attachments/item-masterdata');
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
        return $this->readRecord(ItemMasterdataModel::class, 'Item Masterdata');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ItemMasterdataModel::class, $id, 'Item Masterdata');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemMasterdataModel::class, $id, 'Item Masterdata');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ItemMasterdataModel::class, $id, 'Item Masterdata', $request);
    }
    public function onGetCurrent($id = null)
    {
        $whereFields = null;
        if ($id != null) {
            $whereFields = [
                'item_code' => $id
            ];
        }

        return $this->readCurrentRecord(ItemMasterdataModel::class, $id, $whereFields, null, null, 'Item Masterdata');
    }

    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $bulkUploadData = json_decode($fields['bulk_data'], true);
            $createdById = $fields['created_by_id'];
            foreach ($bulkUploadData as $data) {
                $record = new ItemMasterdataModel();
                $record->item_code = $this->onCheckValue($data['item_code']);
                $record->description = $this->onCheckValue($data['description']);
                $record->short_name = $this->onCheckValue($data['item_short_name']);
                $record->item_category_id = $this->onGetItemCategory($data['item_category_code']);
                $record->item_classification_id = $this->onGetItemClassification($data['item_classification_code']);
                $record->item_variant_type_id = $this->onGetItemVariantType($data['item_variant_type_code']);
                $record->storage_type_id = $this->onGetStorageType($data['storage_type_code']);
                $record->uom_id = $this->onGetUom($data['uom_code']);
                $record->primary_item_packing_size = $this->onCheckValue($data['primary_item_packing_size']);
                $record->primary_conversion_id = $this->onGetPrimaryConversion($data['primary_conversion_code']);
                $record->secondary_item_packing_size = $this->onCheckValue($data['secondary_item_packing_size']);
                $record->secondary_conversion_id = $this->onGetSecondaryConversion($data['secondary_conversion_code']);
                $record->chilled_shelf_life = $this->onCheckValue($data['chilled_shelf_life']);
                $record->frozen_shelf_life = $this->onCheckValue($data['frozen_shelf_life']);
                $record->ambient_shelf_life = $this->onCheckValue($data['ambient_shelf_life']);
                $record->created_by_id = $createdById;
                $record->sticker_remarks_code = $data['sticker_remarks_code'];
                $record->plant_id = $this->onGetPlant($data['plant_code']);
                $record->parent_item_id = $this->onGetParentId($this->onCheckValue($data['parent_code']));
                $record->save();
            }
            DB::commit();
            return $this->dataResponse('success', 201, 'Item Masterdata ' . __('msg.create_success'), $record);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.create_failed'));
        }
    }

    public function onGetParentId($value)
    {
        $item = ItemMasterdataModel::where('item_code', $value)->first();
        return $item->id ?? null;
    }

    public function onCheckValue($value)
    {
        return $value == '' ? null : $value;
    }

    public function onGetItemClassification($value)
    {
        return ItemClassificationModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetItemCategory($value)
    {
        return ItemCategoryModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetStorageType($value)
    {
        return StorageTypeModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetItemVariantType($value)
    {
        return ItemVariantTypeModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetPlant($value)
    {
        return FacilityPlantModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetUom($value)
    {
        return ItemUomModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetPrimaryConversion($value)
    {
        return ItemConversionModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetSecondaryConversion($value)
    {
        return ItemConversionModel::where('code', $value)->first()->id ?? null;
    }
}
