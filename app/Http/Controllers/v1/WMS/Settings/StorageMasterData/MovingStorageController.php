<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\MovingStorageModel;
use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class MovingStorageController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_warehouses,code,' . $itemId,
            'short_name' => 'required|string|unique:wms_storage_warehouses,short_name,' . $itemId,
            'long_name' => 'required|string|unique:wms_storage_warehouses,long_name,' . $itemId,
            'qty' => 'integer|nullable',
            'facility_id' => 'required|integer|exists:wms_storage_facility_plants,id',
            'warehouse_id' => 'required|integer|exists:wms_storage_warehouses,id',
            'zone_id' => 'required|integer|exists:wms_storage_zones,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(MovingStorageModel::class, $request, $this->getRules(), 'Moving Storage');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(MovingStorageModel::class, $request, $this->getRules($id), 'Moving Storage', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code', 'short_name', 'long_name'];
        return $this->readPaginatedRecord(MovingStorageModel::class, $request, $searchableFields, 'Moving Storage');
    }
    public function onGetall()
    {
        return $this->readRecord(MovingStorageModel::class, 'Moving Storage');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(MovingStorageModel::class, $id, 'Moving Storage');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(MovingStorageModel::class, $id, 'Moving Storage');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(MovingStorageModel::class, $id, 'Moving Storage', $request);
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
                $movingStorage = new MovingStorageModel();
                $movingStorage->code = $this->onCheckValue($data['code']);
                $movingStorage->short_name = $this->onCheckValue($data['short_name']);
                $movingStorage->long_name = $this->onCheckValue($data['long_name']);
                $movingStorage->description = $this->onCheckValue($data['description']);
                $movingStorage->facility_id = $this->onGetFacilityId($data['facility_code']);
                $movingStorage->warehouse_id = $this->onGetWarehouseId($data['warehouse_code']);
                $movingStorage->zone_id = $this->onGetZoneId($data['zone_code']);
                $movingStorage->sub_location_id = $this->onGetZoneId($data['sub_location_id']);
                $movingStorage->created_by_id = $createdById;
                $movingStorage->save();
            }
            DB::commit();
            return $this->dataResponse('success', 201, 'Moving Storage ' . __('msg.create_success'));
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

    public function onGetZoneId($value)
    {
        $zoneCode = $this->onCheckValue($value);

        $zone = StorageTypeModel::where('code', $zoneCode)->first();

        return $zone ? $zone->id : null;
    }

    public function onGetSubLocationId($value)
    {
        $subLocationCode = $this->onCheckValue($value);

        $subLocation = SubLocationModel::where('code', $subLocationCode)->first();

        return $subLocation ? $subLocation->id : null;
    }
}
