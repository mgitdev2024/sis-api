<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\StorageTypeModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class SubLocationController extends Controller
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
            'facility_id' => 'required|integer|exists:wms_storage_facility_plants,id',
            'warehouse_id' => 'required|integer|exists:wms_storage_warehouses,id',
            'zone_id' => 'required|integer|exists:wms_storage_zones,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(SubLocationModel::class, $request, $this->getRules(), 'Sub Location');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(SubLocationModel::class, $request, $this->getRules($id), 'Sub Location', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code', 'short_name', 'long_name'];
        return $this->readPaginatedRecord(SubLocationModel::class, $request, $searchableFields, 'Sub Location');
    }
    public function onGetall()
    {
        return $this->readRecord(SubLocationModel::class, 'Sub Location', ['facility', 'warehouse', 'zone']);
    }
    public function onGetChildByParentId($id = null)
    {
        return $this->readRecordByParentId(SubLocationModel::class, 'Sub Location', 'zone_id', $id);
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
                $subLocation = new SubLocationModel();
                $subLocation->code = $this->onCheckValue($data['code']);
                $subLocation->short_name = $this->onCheckValue($data['short_name']);
                $subLocation->long_name = $this->onCheckValue($data['long_name']);
                $subLocation->qty = $this->onCheckValue($data['qty']);
                $subLocation->facility_id = $this->onGetFacilityId($data['facility_code']);
                $subLocation->warehouse_id = $this->onGetWarehouseId($data['warehouse_code']);
                $subLocation->zone_id = $this->onGetZoneId($data['zone_code']);

                $subLocation->created_by_id = $createdById;
                $subLocation->save();
            }
            DB::commit();
            return $this->dataResponse('success', 201, 'Sub Location ' . __('msg.create_success'));
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
}
