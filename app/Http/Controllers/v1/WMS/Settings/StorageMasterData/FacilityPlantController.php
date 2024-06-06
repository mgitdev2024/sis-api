<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\FacilityPlantModel;
use App\Traits\MosCrudOperationsTrait;
use Illuminate\Http\Request;

class FacilityPlantController extends Controller
{
    use MosCrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_facility_plants,code,' . $itemId,
            'short_name' => 'required|string|unique:wms_storage_facility_plants,short_name,' . $itemId,
            'long_name' => 'required|string|unique:wms_storage_facility_plants,long_name,' . $itemId,
            'description' => 'string|nullable'
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(FacilityPlantModel::class, $request, $this->getRules(), 'Facility Plant');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(FacilityPlantModel::class, $request, $this->getRules($id), 'Facility Plant', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code', 'short_name', 'long_name'];
        return $this->readPaginatedRecord(FacilityPlantModel::class, $request, $searchableFields, 'Facility Plant');
    }
    public function onGetall()
    {
        return $this->readRecord(FacilityPlantModel::class, 'Facility Plant');
    }

    public function onGetById($id)
    {
        return $this->readRecordById(FacilityPlantModel::class, $id, 'Facility Plant');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(FacilityPlantModel::class, $id, 'Facility Plant');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(FacilityPlantModel::class, $id, 'Facility Plant', $request);
    }
    public function onBulk(Request $request)
    {
        $fields = $request->validate([
            'created_by_id' => 'required',
            'bulk_data' => 'required'
        ]);
        return $this->bulkUpload(FacilityPlantModel::class, 'Facility Plant', $fields);
    }
}
