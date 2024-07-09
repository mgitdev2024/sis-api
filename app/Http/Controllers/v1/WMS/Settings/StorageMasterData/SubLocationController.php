<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\SubLocationModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationTypeModel;
use App\Traits\MOS\MosCrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class SubLocationController extends Controller
{
    use MosCrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_types,code,' . $itemId,
            'number' => 'integer',
            'is_permanent' => 'integer|nullable',
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
        return $this->createRecord(SubLocationModel::class, $request, $this->getRules(), 'Sub Location Category');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(SubLocationModel::class, $request, $this->getRules($id), 'Sub Location Category', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['number'];
        return $this->readPaginatedRecord(SubLocationModel::class, $request, $searchableFields, 'Sub Location Category');
    }
    public function onGetall()
    {
        return $this->readRecord(SubLocationModel::class, 'Sub Location Category', ['facility', 'warehouse', 'zone']);
    }
    public function onGetChildByParentId($id = null)
    {
        return $this->readRecordByParentId(SubLocationModel::class, 'Sub Location', 'sub_location_type_id', $id, ['facility', 'warehouse', 'zone']);
    }
    public function onGetById($id)
    {
        return $this->readRecordById(SubLocationModel::class, $id, 'Sub Location Category');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(SubLocationModel::class, $id, 'Sub Location Category');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(SubLocationModel::class, $id, 'Sub Location Category', $request);
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
        $layers = array_keys(json_decode($subLocationModel->layers, true));
        $subLocationCodes['storage_type'] = SubLocationModel::onGenerateStorageCode($id)['storage_type'];
        foreach ($layers as $keys) {
            $subLocationCodes['storage_codes'][] = SubLocationModel::onGenerateStorageCode($id, $keys)['storage_code'];
        }

        return $this->dataResponse('success', 201, 'Storage Warehouse ' . __('msg.record_found'), $subLocationCodes);
    }
}
