<?php

namespace App\Http\Controllers\v1\Access;

use App\Http\Controllers\Controller;
use App\Models\Access\ModulePermissionModel;
use App\Models\Access\SubModulePermissionModel;
use Illuminate\Http\Request;
use Exception;
use App\Traits\ResponseTrait;
use DB;

class AccessManagementController extends Controller
{
    use ResponseTrait;
    public function onGetAccess(Request $request)
    {
        $fields = $request->validate([
            'type' => 'required',
            'code' => 'required',
            'emp_no' => 'required'
        ]);
        try {
            $empno = $fields['emp_no'];
            $type = strcasecmp($fields['type'], 'module') == 0 ? ModulePermissionModel::class : SubModulePermissionModel::class;
            $checkAccess = $type::select([
                DB::raw("IF(JSON_CONTAINS(CAST(is_enabled AS JSON), '" . $empno . "'), true, false) as is_enabled"),
                DB::raw("IF(JSON_CONTAINS(CAST(allow_view AS JSON), '" . $empno . "'), true, false) as allow_view"),
                DB::raw("IF(JSON_CONTAINS(CAST(allow_create AS JSON), '" . $empno . "'), true, false) as allow_create"),
                DB::raw("IF(JSON_CONTAINS(CAST(allow_update AS JSON), '" . $empno . "'), true, false) as allow_update"),
                DB::raw("IF(JSON_CONTAINS(CAST(allow_delete AS JSON), '" . $empno . "'), true, false) as allow_delete"),
            ])
                ->where('code', $fields['code'])
                ->first();
            return $this->dataResponse('success', 200, __('msg.record_found'), $checkAccess);

        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onUpdateAccess(Request $request)
    {
        $fields = $request->validate([
            'action' => 'required',
            'type' => 'required',
            'code' => 'required',
            'emp_no' => 'required'
        ]);

        try {
            DB::beginTransaction();
            $type = strcasecmp($fields['type'], 'module') == 0 ? ModulePermissionModel::class : SubModulePermissionModel::class;
            $employeeList = json_decode($fields['emp_no'], true);
            $action = $fields['action'];
            $permissionTable = $type::where('code', $fields['code'])
                ->first();
            $access = [];
            if ($permissionTable) {
                $access = json_decode($permissionTable->$action) ?? [];
                foreach ($employeeList as $employee) {
                    if (!in_array($employee, $access)) {
                        $access[] = $employee;
                    }
                }
                $permissionTable->$action = json_encode($access);
                $permissionTable->save();
                DB::commit();
                return $this->dataResponse('success', 200, __('msg.update_success'), $permissionTable);
            }
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.update_failed'));
        }
    }

    public function onRemoveAccess(Request $request)
    {
        $fields = $request->validate([
            'action' => 'required',
            'type' => 'required',
            'code' => 'required',
            'emp_no' => 'required'
        ]);

        try {
            DB::beginTransaction();
            $type = strcasecmp($fields['type'], 'module') == 0 ? ModulePermissionModel::class : SubModulePermissionModel::class;
            $employeeList = json_decode($fields['emp_no'], true);
            $action = $fields['action'];
            $permissionTable = $type::where('code', $fields['code'])->first();

            if ($permissionTable) {
                $access = json_decode($permissionTable->$action, true) ?? [];
                $access = array_values(array_diff($access, $employeeList));
                $permissionTable->$action = json_encode($access);
                $permissionTable->save();
                DB::commit();

                return $this->dataResponse('success', 200, __('msg.update_success'), $permissionTable);
            }

            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->dataResponse('error', 400, __('msg.update_failed'));
        }
    }

    public function onGetAccessList($id)
    {
        try {
            $modulePermissionList = ModulePermissionModel::with('subModulePermissions')->get();
            $permissionList = [];

            foreach ($modulePermissionList as $module) {
                $subModuleArr = [];

                foreach ($module->subModulePermissions as $subModule) {
                    $subModuleArr[$subModule['code']] = [
                        'name' => $subModule['name'],
                        'is_enabled' => $this->onIsEnabled($subModule['is_enabled'], $id),
                    ];
                }

                $permissionList[$module['code']] = [
                    'name' => $module['name'],
                    'is_enabled' => $this->onIsEnabled($module['is_enabled'], $id),
                    'submodules' => $subModuleArr
                ];
            }

            return $this->dataResponse('success', 200, __('msg.record_found'), $permissionList);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onIsEnabled($isEnabledArr, $id)
    {
        $isEnabled = json_decode($isEnabledArr, true);

        return in_array($id, $isEnabled);
    }
}
