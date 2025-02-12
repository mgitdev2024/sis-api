<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationTypeModel;
use App\Models\WMS\Storage\QueuedSubLocationModel;
use App\Traits\MOS\MosCrudOperationsTrait;
use App\Traits\WMS\QueueSubLocationTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class SubLocationController extends Controller
{
    use MosCrudOperationsTrait, QueueSubLocationTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_sub_locations,code,' . $itemId,
            'number' => 'integer',
            'is_permanent' => 'boolean|nullable',
            'has_layer' => 'integer|nullable',
            'layers' => 'string|nullable',
            'facility_id' => 'required|integer|exists:wms_storage_facility_plants,id',
            'warehouse_id' => 'nullable|integer|exists:wms_storage_warehouses,id',
            'zone_id' => 'nullable|integer|exists:wms_storage_zones,id',
            'sub_location_type_id' => 'required|integer|exists:wms_storage_sub_location_type,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(SubLocationModel::class, $request, $this->getRules(), 'Sub Location');
    }
    public function onUpdateById(Request $request, $id)
    {
        $fields = $request->validate($this->getRules($id));
        try {
            $record = new SubLocationModel();
            $record = SubLocationModel::find($id);
            if ($record) {
                DB::beginTransaction();
                $this->createProductionLog(SubLocationModel::class, $record->id, $fields, $fields['updated_by_id'], 1);

                foreach (json_decode($fields['layers'], true) as $layer) {
                    $storageRemainingSpaceUpdate = QueuedSubLocationModel::where([
                        'sub_location_id' => $id,
                        'layer_level' => $layer['layer_no']
                    ])->orderBy('id', 'DESC')->first();
                    $subLocationModel = $storageRemainingSpaceUpdate->subLocation ?? null;
                    if ($storageRemainingSpaceUpdate) {
                        $originalLayerSpace = json_decode($subLocationModel->layers, true)[$layer['layer_no']]['max'];
                        if ($originalLayerSpace < $layer['max']) {
                            $additionalSpace = $layer['max'] - $originalLayerSpace;
                            $storageRemainingSpaceUpdate->storage_remaining_space += $additionalSpace;
                            $storageRemainingSpaceUpdate->save();
                        } else if (($originalLayerSpace > $layer['max'])) {
                            $reducedSpace = $originalLayerSpace - $layer['max'];
                            if ($storageRemainingSpaceUpdate->storage_remaining_space < $reducedSpace) {
                                throw new Exception('Layer space cannot be reduced. Please check the storage');
                            }
                            $storageRemainingSpaceUpdate->storage_remaining_space -= $reducedSpace;
                            $storageRemainingSpaceUpdate->save();
                        }
                    }
                }
                $record->update($fields);
                DB::commit();
                return $this->dataResponse('success', 201, 'Sub Location ' . __('msg.update_success'), $record);
            }
            return $this->dataResponse('error', 200, 'Sub Location ' . __('msg.update_failed'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['number'];
        return $this->readPaginatedRecord(SubLocationModel::class, $request, $searchableFields, 'Sub Location');
    }
    public function onGetall()
    {
        return $this->readRecord(SubLocationModel::class, 'Sub Location', ['facility', 'warehouse', 'zone']);
    }
    public function onGetChildByParentId($id = null)
    {
        return $this->readRecordByParentId(SubLocationModel::class, 'Sub Location', 'sub_location_type_id', $id, ['facility', 'warehouse', 'zone']);
    }
    public function onGetById($id)
    {
        return $this->readRecordById(SubLocationModel::class, $id, 'Sub Location');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(SubLocationModel::class, $id, 'Sub Location');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(SubLocationModel::class, $id, 'Sub Location', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $bulkUploadData = json_decode($fields['bulk_data'], true);
            $createdById = $fields['created_by_id'];

            foreach ($bulkUploadData as $data) {
                $storageWarehouse = new SubLocationModel();
                $storageWarehouse->code = $this->onCheckValue($data['code']);
                $storageWarehouse->number = $this->onCheckValue($data['number']);
                $storageWarehouse->has_layer = $this->onCheckValue($data['has_layer']);
                $storageWarehouse->facility_id = $this->onGetFacilityId($data['facility_code']);
                $storageWarehouse->warehouse_id = $this->onGetWarehouseId($data['warehouse_code']);
                $storageWarehouse->zone_id = $this->onGetZoneId($data['zone_code']);
                $storageWarehouse->sub_location_type_id = $this->onGetSubLocationId($data['sub_location_type_id']);
                $storageWarehouse->created_by_id = $createdById;
                $storageWarehouse->save();
            }
            DB::commit();
            return $this->dataResponse('success', 201, 'Storage Warehouse ' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollback();
            if ($exception instanceof \Illuminate\Database\QueryException && $exception->errorInfo[1] == 1364) {
                preg_match("/Field '(.*?)' doesn't have a default value/", $exception->getMessage(), $matches);
                return $this->dataResponse('error', 400, __('Field ":field" requires a default value.', ['field' => $matches[1] ?? 'unknown field']));
            }
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onCheckValue($value)
    {
        return $value == '' ? null : $value;
    }

    public function onGetSubLocationId($value)
    {
        $subLocationCode = $this->onCheckValue($value);

        $subLocation = SubLocationTypeModel::where('code', $subLocationCode)->first();

        return $subLocation ? $subLocation->id : null;
    }

    public function onGenerateCode($id)
    {
        $subLocationCodes = [];

        $subLocationModel = SubLocationModel::find($id);
        $layers = json_decode($subLocationModel->layers, true);
        $subLocationCodes['storage_type'] = SubLocationModel::onGenerateStorageCode($id)['storage_type'];
        foreach ($layers as $value) {
            $subLocationCodes['storage_codes'][] = SubLocationModel::onGenerateStorageCode($id, $value['layer_no'])['storage_code'];
        }

        return $this->dataResponse('success', 201, 'Storage Warehouse ' . __('msg.record_found'), $subLocationCodes);
    }

    public function onGenerateCodeAll()
    {
        try {
            $subLocation = [];

            $subLocationModel = SubLocationModel::all();

            foreach ($subLocationModel as $subLocations) {

                $hasLayer = $subLocations['has_layer'];
                if ($hasLayer == 1) {
                    foreach (json_decode($subLocations->layers, true) as $layers) {
                        $subLocationCodes = SubLocationModel::onGenerateStorageCode($subLocations['id'], $layers['layer_no'])['storage_code'];
                        $subLocation[] = $subLocationCodes;
                    }
                } else {
                    $subLocationCodes = SubLocationModel::onGenerateStorageCode($subLocations['id'])['storage_type'];
                    $subLocation[] = $subLocationCodes;
                }
            }
            return $this->dataResponse('success', 201, 'Storage Warehouse ' . __('msg.record_found'), $subLocation);

        } catch (Exception $exception) {
            return $this->dataResponse('success', 201, 'Storage Warehouse ' . __('msg.record_not_found'), $subLocation);

        }
    }

    public function onGenerateSubLocation(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'is_permanent' => 'required|in:0,1',
            'has_layer' => 'required|in:0,1',
            'layers' => 'required_if:has_layer,1',
            'number' => 'required',
            'facility_id' => 'nullable|integer|exists:wms_storage_facility_plants,id',
            'warehouse_id' => 'nullable|integer|exists:wms_storage_warehouses,id',
            'zone_id' => 'nullable|integer|exists:wms_storage_zones,id',
            'sub_location_type_id' => 'required|integer|exists:wms_storage_sub_location_type,id',
            'quantity' => 'required|integer',
            'base_code' => 'required',
        ]);
        try {
            DB::beginTransaction();
            // Get the latest sub-location code based on the base code
            $latestSubLocation = SubLocationModel::where('code', 'LIKE', "%{$fields['base_code']}%")
                ->orderBy('code', 'DESC')
                ->first();

            $nextCode = $fields['base_code'] . str_pad('001', 3, '0', STR_PAD_LEFT); // Default start
            if ($latestSubLocation) {
                // Extract the number part from the latest sub-location code and increment it
                $lastNumber = intval(substr($latestSubLocation->code, strlen($fields['base_code'])));
                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                $nextCode = $fields['base_code'] . $nextNumber;
            }

            for ($i = 0; $i < $fields['quantity']; $i++) {

                DB::table('wms_storage_sub_locations')->insert([
                    'code' => $nextCode,
                    'created_by_id' => $fields['created_by_id'],
                    'is_permanent' => $fields['is_permanent'] ?? 0,
                    'has_layer' => $fields['has_layer'] ?? 0,
                    'number' => $fields['number'],
                    'layers' => $fields['layers'] ?? null,
                    'facility_id' => $fields['facility_id'] ?? null,
                    'warehouse_id' => $fields['warehouse_id'] ?? null,
                    'zone_id' => $fields['zone_id'] ?? null,
                    'sub_location_type_id' => $fields['sub_location_type_id'],
                    'status' => 1, // Assuming status is active by default
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $nextCode = $fields['base_code'] . str_pad((intval(substr($nextCode, strlen($fields['base_code'])))) + 1, 3, '0', STR_PAD_LEFT);
            }

            DB::commit();
            return $this->dataResponse('success', 201, 'Sub Location ' . __('msg.create_success'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onForceBulkTemporaryStorageLocation(Request $request)
    {
        $fields = $request->validate([
            'base_code' => 'required',
            'number_to_add' => 'required',
            'created_by_id' => 'required',
            'sub_location_type' => 'required',
        ]);
        try {
            $baseCode = $fields['base_code'];
            $numberToAdd = $fields['number_to_add'];
            $createdById = $fields['created_by_id'];
            $subLocationType = $fields['sub_location_type'];
            DB::beginTransaction();
            // Get the latest sub-location code based on the base code
            $latestSubLocation = SubLocationModel::where('code', 'LIKE', "%{$baseCode}%")
                ->orderBy('id', 'DESC')
                ->first();

            $nextCode = $baseCode . str_pad('001', 3, '0', STR_PAD_LEFT); // Default start
            if ($latestSubLocation) {
                // Extract the number part from the latest sub-location code and increment it
                $lastNumber = intval(substr($latestSubLocation->code, strlen($baseCode)));
                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                $nextCode = $baseCode . $nextNumber;
            }
            for ($i = 0; $i < $numberToAdd; $i++) {
                $subLocationModel = new SubLocationModel();
                $subLocationModel->code = $nextCode;
                $subLocationModel->created_by_id = $createdById;
                $subLocationModel->is_permanent = 0;
                $subLocationModel->has_layer = 1;
                $subLocationModel->number = 1;
                $subLocationModel->layers = '{"1":{"min":1,"max":500,"layer_no":1}}';
                $subLocationModel->facility_id = 1;
                $subLocationModel->warehouse_id = null;
                $subLocationModel->zone_id = null;
                $subLocationModel->sub_location_type_id = $subLocationType;
                $subLocationModel->save();
                $nextCode = $baseCode . str_pad((intval(substr($nextCode, strlen($baseCode)))) + 1, 3, '0', STR_PAD_LEFT);
            }
            DB::commit();
            return $this->dataResponse('success', 201, 'Sub Location ' . __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function onSubLocationDetails($sub_location_id, $is_permanent, $layer_level = null)
    {
        try {
            if ($is_permanent == 1) {
                $data = $this->onGetSubLocationDetails($sub_location_id, $layer_level, true);

            } else {
                $data = $this->onGetSubLocationDetails($sub_location_id, $layer_level, false);

            }
            return $this->dataResponse('success', 200, __('msg.record_found'), $data);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }
}
