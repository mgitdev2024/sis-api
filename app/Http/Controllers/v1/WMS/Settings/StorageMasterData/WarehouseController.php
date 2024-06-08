<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Models\WMS\Settings\StorageMasterData\WarehouseModel;
use App\Traits\MOS\MosCrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class WarehouseController extends Controller
{
    use MosCrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_warehouses,code,' . $itemId,
            'short_name' => 'required|string|unique:wms_storage_warehouses,short_name,' . $itemId,
            'long_name' => 'required|string|unique:wms_storage_warehouses,long_name,' . $itemId,
            'description' => 'string|nullable',
            'facility_id' => 'required|integer|exists:wms_storage_facility_plants,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(WarehouseModel::class, $request, $this->getRules(), 'Warehouse');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(WarehouseModel::class, $request, $this->getRules($id), 'Warehouse', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code', 'short_name', 'long_name'];
        return $this->readPaginatedRecord(WarehouseModel::class, $request, $searchableFields, 'Warehouse');
    }
    public function onGetall()
    {
        return $this->readRecord(WarehouseModel::class, 'Warehouse');
    }
    public function onGetChildByParentId($id = null)
    {
        return $this->readRecordByParentId(WarehouseModel::class, 'Warehouse', 'facility_id', $id);
    }
    public function onGetById($id)
    {
        return $this->readRecordById(WarehouseModel::class, $id, 'Warehouse');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(WarehouseModel::class, $id, 'Warehouse');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(WarehouseModel::class, $id, 'Warehouse', $request);
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
                $storageWarehouse = new WarehouseModel();
                $storageWarehouse->code = $this->onCheckValue($data['code']);
                $storageWarehouse->short_name = $this->onCheckValue($data['short_name']);
                $storageWarehouse->long_name = $this->onCheckValue($data['long_name']);
                $storageWarehouse->description = $this->onCheckValue($data['description']);
                $storageWarehouse->facility_id = $this->onGetFacilityId($data['facility_code']);
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

    public function onGetFacilityId($value)
    {
        $facilityCode = $this->onCheckValue($value);

        $facility = FacilityPlantModel::where('code', $facilityCode)->first();

        return $facility ? $facility->id : null;
    }
}
