<?php

namespace App\Http\Controllers\SystemConfiguration;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration\ModuleFunction;
use App\Models\SystemConfiguration\ModulePermission;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use Symfony\Component\HttpFoundation\Response;

class ModulePermissionController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules($id = null)
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'permission_name' => 'required|string|unique:module_permissions,permission_name,' . $id,
            'status' => 'nullable|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ModulePermission::class, $request, $this->getRules(), 'Module Function');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ModulePermission::class, $id, 'Module Function');
    }

    public function onGetAll()
    {
        return $this->readRecord(ModulePermission::class, 'Module Function');
    }

    public function onGetByToggledPermission($id)
    {
        try {
            $toggledModulePermissionId = ModuleFunction::where('sub_module_id', $id)->get('module_permission_id');
            $toggleModulePermissionArr = [];
            foreach ($toggledModulePermissionId as $id) {
                array_push($toggleModulePermissionArr, $id->module_permission_id);
            }

            $modulePermission = ModulePermission::whereNotIn('id', $toggleModulePermissionArr)->get();
            $reconstructedList = [];
            foreach ($modulePermission as $data) {
                $response = $data->toArray();
                $response['created_by'] = $data->createdBy->first_name . ' ' . $data->createdBy->middle_name . ' ' . $data->createdBy->last_name;
                if (isset($data->updated_by_id)) {
                    $response['updated_by'] = $data->updatedBy->first_name . ' ' . $data->updatedBy->middle_name . ' ' . $data->updatedBy->last_name;
                }
                $reconstructedList[] = $response;
            }
            return $this->dataResponse('success', Response::HTTP_OK, __('msg.record_found'), $reconstructedList);
        } catch (\Exception $exception) {
            return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, $exception->getMessage());
        }
    }


    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ModulePermission::class, $request, $this->getRules($id), 'Module Function', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ModulePermission::class, $id, 'Module Function');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ModulePermission::class, $id, 'Module Function');
    }
}
