<?php

namespace App\Http\Controllers\v1\WMS\Settings\ItemMasterData;

use App\Http\Controllers\Controller;
use App\Models\MOS\Production\ProductionBatchModel;
use App\Models\MOS\Production\ProductionOTAModel;
use App\Models\MOS\Production\ProductionOTBModel;
use App\Models\QualityAssurance\ItemDispositionModel;
use App\Models\QualityAssurance\SubStandardItemModel;
use App\Models\WMS\InventoryKeeping\StockTransfer\StockTransferItemModel;
use App\Models\WMS\Settings\ItemMasterData\ItemClassificationModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMasterdataModel;
use App\Models\WMS\Settings\ItemMasterData\ItemCategoryModel;
use App\Models\WMS\Settings\ItemMasterData\ItemConversionModel;
use App\Models\WMS\Settings\ItemMasterData\ItemMovementModel;
use App\Models\WMS\Settings\ItemMasterData\ItemStockTypeModel;
use App\Models\WMS\Settings\ItemMasterData\ItemUomModel;
use App\Models\WMS\Settings\ItemMasterData\ItemVariantTypeModel;
use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use App\Models\WMS\Storage\StockInventoryModel;
use App\Models\WMS\Storage\StockLogModel;
use App\Models\WMS\Warehouse\WarehousePutAwayModel;
use App\Models\WMS\Warehouse\WarehouseReceivingModel;
use Illuminate\Http\Request;
use App\Traits\MOS\MosCrudOperationsTrait;
use DB;
use Exception;

class ItemMasterdataController extends Controller
{
    use MosCrudOperationsTrait;

    public static function getRules($itemId = null)
    {

        return [
            'created_by_id' => 'nullable',
            'updated_by_id' => 'nullable',
            'item_code' => 'required|string|unique:wms_item_masterdata,item_code,' . $itemId,
            'description' => 'required|string',
            'short_name' => 'required|string',
            'long_name' => 'nullable|string',
            'unit_price' => 'nullable|numeric',
            'parent_item_id' => 'nullable|string',
            'item_category_id' => 'required|integer|exists:wms_item_categories,id',
            'item_classification_id' => 'required|integer|exists:wms_item_categories,id',
            'item_variant_type_id' => 'required|integer|exists:wms_item_variant_types,id',
            'uom_id' => 'required|integer|exists:wms_item_uoms,id',
            'storage_type_id' => 'required|integer|exists:wms_storage_types,id',
            'actual_storage_type_id' => 'required|integer|exists:wms_storage_types,id',
            'warehouse_location_id' => 'nullable|integer|exists:wms_storage_warehouses,id',
            'zone_id' => 'nullable|integer|exists:wms_storage_zones,id',
            'stock_type_id' => 'required|integer|exists:wms_item_stock_types,id',
            'item_movement_id' => 'required|integer|exists:wms_item_movements,id',
            'delivery_lead_time' => 'nullable|integer',
            'inbound_shelf_life' => 'nullable|integer',
            'outbound_shelf_life' => 'nullable|integer',
            're_order_level' => 'nullable|integer',
            'max_qty' => 'nullable|integer',
            'stock_rotation_type' => 'nullable|string',
            'qty_per_pallet' => 'nullable|integer',
            'dimension_l' => 'nullable|string',
            'dimension_h' => 'nullable|string',
            'dimension_w' => 'nullable|string',
            'item_weight' => 'nullable|string',
            'is_viewable_by_otb' => 'nullable|integer',
            'is_qa_required' => 'nullable|integer',
            'is_qa_disposal' => 'nullable|integer',
            'attachment' => 'nullable',
            'primary_item_packing_size' => 'nullable|integer',
            'primary_conversion_id' => 'nullable|integer|exists:wms_item_conversions,id',
            'secondary_item_packing_size' => 'nullable|integer',
            'secondary_conversion_id' => 'nullable|integer|exists:wms_item_conversions,id',
            'ambient_shelf_life' => 'nullable|integer',
            'chilled_shelf_life' => 'nullable|integer',
            'frozen_shelf_life' => 'nullable|integer',
            'sticker_remarks_code' => 'required|string',
            'plant_id' => 'required|integer|exists:wms_storage_facility_plants,id',
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ItemMasterdataModel::class, $request, $this->getRules(), 'Item Masterdata', 'public/attachments/item-masterdata');
    }
    public function onUpdateById(Request $request, $id)
    {
        $fields = $request->validate($this->getRules($id));
        try {
            $record = new ItemMasterdataModel();
            $record = ItemMasterdataModel::find($id);
            if ($record) {
                $this->onTablesUpdate($fields['item_code'], $record->item_code, $fields['updated_by_id']);

                $hasParentItemId = $fields['parent_item_id'] ?? null;

                if ($hasParentItemId == null) {
                    $fields['parent_item_id'] = null;
                }
                $record->update($fields);
                if ($request->hasFile('attachment')) {
                    $attachmentPath = $request->file('attachment')->store('public/attachments/item-masterdata');
                    $filepath = env('APP_URL') . '/storage/' . substr($attachmentPath, 7);
                    $record->attachment = $filepath;
                    $record->save();
                }
                $this->createProductionLog(ItemMasterdataModel::class, $record->id, $fields, $fields['updated_by_id'], 1);
                return $this->dataResponse('success', 201, 'Item Masterdata ' . __('msg.update_success'), $record);
            }
            return $this->dataResponse('error', 200, 'Item Masterdata ' . __('msg.update_failed'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onTablesUpdate($newItemCode, $oldItemCode, $updatedById)
    {
        $tables = [
            'mos_production_otas' => ProductionOTAModel::class,
            'mos_production_otbs' => ProductionOTBModel::class,
            'mos_production_batches' => ProductionBatchModel::class,
            'wms_warehouse_receiving' => WarehouseReceivingModel::class,
            // 'wms_warehouse_put_away' =>WarehousePutAwayModel::class,
            // 'wms_warehouse_for_put_away' => null,
            // 'wms_stock_inventories' =>StockInventoryModel::class,
            // 'wms_stock_logs' =>StockLogModel::class,
            // 'wms_stock_transfer_items' =>StockTransferItemModel::class,
            'qa_sub_standard_items' => SubStandardItemModel::class,
            'qa_item_dispositions' => ItemDispositionModel::class,
        ];
        foreach ($tables as $tableName => $modelClass) {
            $record = DB::table($tableName)->where('item_code', $oldItemCode)->get();

            if (count($record) > 0) {
                foreach ($record as $data) {
                    $affectedData = $modelClass::find($data->id);
                    $affectedData->item_code = $newItemCode;
                    $affectedData->updated_by_id = $updatedById;
                    $affectedData->save();

                    $this->createProductionLog($modelClass, $affectedData->id, $affectedData->getAttributes(), $updatedById, 1);
                }
            }
        }
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
        try {
            $itemMasterdata = ItemMasterdataModel::find($id);
            if ($itemMasterdata) {
                $itemMasterdata->original_item_code = $itemMasterdata->item_code;
                return $this->dataResponse('success', 200, 'Item Masterdata' . __('msg.record_found'), $itemMasterdata);
            }
            return $this->dataResponse('error', 200, 'Item Masterdata ' . __('msg.record_not_found'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ItemMasterdataModel::class, $id, 'Item Masterdata');
    }
    public function onChangeStatus(Request $request, $status)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'selected_items' => 'required|json',
        ]);
        try {
            $selectedItems = json_decode($fields['selected_items'], true);

            if ($selectedItems == null || count($selectedItems) <= 0) {
                return $this->dataResponse('error', 200, 'Item Masterdata ' . __('msg.update_failed'));
            }

            foreach ($selectedItems as $items) {
                $data = ItemMasterdataModel::find($items);
                if ($data) {
                    $data->status = $status;
                    $data->updated_by_id = $fields['created_by_id'];
                    $data->save();
                    $this->createProductionLog(ItemMasterdataModel::class, $data->id, $data, $fields['created_by_id'], 1);
                }
            }
            return $this->dataResponse('success', 200, __('msg.update_success'));
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
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

    public function onGetLikeData($itemCode)
    {
        $whereFields = [
            'item_code' => [
                'operator' => '!=',
                'value' => $itemCode
            ]
        ];
        $baseCode = explode(' ', $itemCode)[0];
        return $this->readLikeRecord(ItemMasterdataModel::class, 'Item Masterdata', 'item_code', $baseCode, $whereFields);
    }

    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'nullable',
            'bulk_data' => 'nullable',
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
                $record->long_name = $this->onCheckValue($data['item_long_name']);
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

                // Added Data
                $record->item_movement_id = $this->onGetItemMovement($data['item_movement_code']);
                $record->stock_type_id = $this->onGetStockType($data['stock_type_code']);
                $record->unit_price = $this->onCheckValue($data['unit_price']);
                $record->zone_id = $this->onGetZone($data['zone_code']);
                $record->delivery_lead_time = $this->onCheckValue($data['delivery_lead_time']);
                $record->inbound_shelf_life = $this->onCheckValue($data['inbound_shelf_life']);
                $record->outbound_shelf_life = $this->onCheckValue($data['outbound_shelf_life']);
                $record->re_order_level = $this->onCheckValue($data['re_order_level']);
                $record->stock_rotation_type = $this->onCheckValue($data['stock_rotation_type']);
                $record->qty_per_pallet = $this->onCheckValue($data['qty_per_pallet']);
                $record->max_qty = $this->onCheckValue($data['max_qty']);
                $record->dimension_l = $this->onCheckValue($data['dimension_l']);
                $record->dimension_h = $this->onCheckValue($data['dimension_h']);
                $record->dimension_w = $this->onCheckValue($data['dimension_w']);
                $record->item_weight = $this->onCheckValue($data['item_weight']);
                $record->is_viewable_by_otb = $this->onBooleanConversion($data['is_viewable_by_otb']);
                $record->is_qa_required = $this->onBooleanConversion($data['is_qa_required']);
                $record->is_qa_disposal = $this->onBooleanConversion($data['is_qa_disposal']);
                $record->warehouse_location_id = $this->onGetWarehouseLocation($data['warehouse_location_code']);
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
        $parentCodes = explode(',', $value);
        $parentIds = [];
        foreach ($parentCodes as $code) {
            $parent = ItemMasterdataModel::where('item_code', $code)->first();
            if ($parent) {
                $parentIds[] = $parent->id;
            }
        }
        return count($parentIds) > 0 ? json_encode($parentIds) : null;
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
        return FacilityPlantModel::where('code', intval($value))->first()->id ?? null;
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
    public function onGetItemMovement($value)
    {
        return ItemMovementModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetStockType($value)
    {
        return ItemStockTypeModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetZone($value)
    {
        return ZoneModel::where('code', $value)->first()->id ?? null;
    }
    public function onGetWarehouseLocation($value)
    {
        return WarehouseModel::where('code', $value)->first()->id ?? null;
    }
    public function onBooleanConversion($value)
    {
        return strcasecmp($value, 'yes') == 0 ? true : false;
    }
}
