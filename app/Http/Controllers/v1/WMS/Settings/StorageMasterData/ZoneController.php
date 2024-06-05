<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;
use App\Models\WMS\Settings\StorageMasterData\ZoneModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class ZoneController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_zones,code,' . $itemId,
            'short_name' => 'required|string|unique:wms_storage_zones,short_name,' . $itemId,
            'long_name' => 'required|string|unique:wms_storage_zones,long_name,' . $itemId,
            'description' => 'string|nullable',
            'facility_id' => 'required|integer|exists:wms_storage_facility_plants,id',
            'warehouse_id' => 'required|integer|exists:wms_storage_warehouses,id',
            'storage_type_id' => 'required|integer|exists:wms_storage_types,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ZoneModel::class, $request, $this->getRules(), 'Zone');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ZoneModel::class, $request, $this->getRules($id), 'Zone', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code', 'short_name', 'long_name'];
        return $this->readPaginatedRecord(ZoneModel::class, $request, $searchableFields, 'Zone');
    }
    public function onGetall()
    {
        return $this->readRecord(ZoneModel::class, 'Zone', ['storage_type', 'warehouse']);
    }
    public function onGetChildByParentId($id = null)
    {
        return $this->readRecordByParentId(ZoneModel::class, 'Zone', 'warehouse_id', $id);
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ZoneModel::class, $id, 'Zone');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ZoneModel::class, $id, 'Zone');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(ZoneModel::class, $id, 'Zone', $request);
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
                $zone = new ZoneModel();
                $zone->code = $this->onCheckValue($data['code']);
                $zone->short_name = $this->onCheckValue($data['short_name']);
                $zone->long_name = $this->onCheckValue($data['long_name']);
                $zone->description = $this->onCheckValue($data['description']);
                $zone->facility_id = $this->onGetFacilityId($data['facility_code']);
                $zone->warehouse_id = $this->onGetWarehouseId($data['warehouse_code']);
                $zone->storage_type_id = $this->onGetStorageTypeId($data['storage_type_code']);

                $zone->created_by_id = $createdById;
                $zone->save();
            }
            DB::commit();
            return $this->dataResponse('success', 201, 'Zone ' . __('msg.create_success'));
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

    public function onGetFacilityId($value)
    {
        $facilityCode = $this->onCheckValue($value);

        $facility = FacilityPlantModel::where('code', $facilityCode)->first();

        return $facility ? $facility->id : null;
    }

    public function onGetWarehouseId($value)
    {
        $warehouseCode = $this->onCheckValue($value);

        $warehouse = WarehouseModel::where('code', $warehouseCode)->first();

        return $warehouse ? $warehouse->id : null;
    }

    public function onGetStorageTypeId($value)
    {
        $storageTypeId = $this->onCheckValue($value);

        $storageType = StorageTypeModel::where('code', $storageTypeId)->first();

        return $storageType ? $storageType->id : null;
    }
}
