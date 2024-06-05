<?php

namespace App\Http\Controllers\v1\WMS\Settings\StorageMasterData;

use App\Http\Controllers\Controller;
use App\Models\WMS\Settings\StorageMasterData\SubLocationCategoryModel;
use App\Models\WMS\Settings\StorageMasterData\SubLocationTypeModel;
use App\Traits\CrudOperationsTrait;
use Illuminate\Http\Request;
use DB;
use Exception;

class SubLocationCategoryController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules($itemId = null)
    {
        return [
            'created_by_id' => 'required',
            'updated_by_id' => 'nullable',
            'code' => 'required|string|unique:wms_storage_types,code,' . $itemId,
            'number' => 'integer',
            'has_layer' => 'integer|nullable',
            'layers' => 'string|nullable',
            'sub_location_type_id' => 'required|integer|exists:wms_storage_sub_location_type,id',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(SubLocationCategoryModel::class, $request, $this->getRules(), 'Sub Location Category');
    }
    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(SubLocationCategoryModel::class, $request, $this->getRules($id), 'Sub Location Category', $id);
    }
    public function onGetPaginatedList(Request $request)
    {
        $searchableFields = ['code', 'short_name', 'long_name'];
        return $this->readPaginatedRecord(SubLocationCategoryModel::class, $request, $searchableFields, 'Sub Location Category');
    }
    public function onGetall()
    {
        return $this->readRecord(SubLocationCategoryModel::class, 'Sub Location Category');
    }
    public function onGetChildByParentId($id = null)
    {
        return $this->readRecordByParentId(SubLocationCategoryModel::class, 'Sub Location', 'sub_location_type_id', $id);
    }
    public function onGetById($id)
    {
        return $this->readRecordById(SubLocationCategoryModel::class, $id, 'Sub Location Category');
    }
    public function onDeleteById($id)
    {
        return $this->deleteRecordById(SubLocationCategoryModel::class, $id, 'Sub Location Category');
    }
    public function onChangeStatus(Request $request, $id)
    {
        return $this->changeStatusRecordById(SubLocationCategoryModel::class, $id, 'Sub Location Category', $request);
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
                $storageWarehouse = new SubLocationCategoryModel();
                $storageWarehouse->code = $this->onCheckValue($data['code']);
                $storageWarehouse->number = $this->onCheckValue($data['number']);
                $storageWarehouse->has_layer = $this->onCheckValue($data['has_layer']);
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
}
