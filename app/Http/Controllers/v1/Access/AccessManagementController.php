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


    public function onGetAccessInfo()
    {
        try {
            $modulePermissionList = ModulePermissionModel::with('subModulePermissions')->get();
            $permissionList = [];

            foreach ($modulePermissionList as $module) {
                $subModuleArr = [];

                foreach ($module->subModulePermissions as $subModule) {
                    $subModuleArr[$subModule['code']] = [
                        'name' => $subModule['name'],
                        'allow_view' => json_decode($subModule['allow_view'], true) ?? [],
                        'allow_create' => json_decode($subModule['allow_create'], true) ?? [],
                        'allow_update' => json_decode($subModule['allow_update'], true) ?? [],
                        'allow_delete' => json_decode($subModule['allow_delete'], true) ?? [],
                        'allow_reopen' => json_decode($subModule['allow_reopen'], true) ?? [],
                    ];
                }

                $permissionList[$module['code']] = [
                    'name' => $module['name'],
                    'allow_view' => json_decode($module['allow_view'], true) ?? [],
                    'allow_create' => json_decode($module['allow_create'], true) ?? [],
                    'allow_update' => json_decode($module['allow_update'], true) ?? [],
                    'allow_delete' => json_decode($module['allow_delete'], true) ?? [],
                    'allow_reopen' => json_decode($module['allow_reopen'], true) ?? [],
                    'sub_module' => $subModuleArr,
                ];
            }

            return $this->dataResponse('success', 200, __('msg.record_found'), $permissionList);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

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
                DB::raw("IF(JSON_CONTAINS(CAST(allow_reopen AS JSON), '" . $empno . "'), true, false) as allow_reopen"),

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
                    $isEnabledFlag = $this->onIsAllowed($subModule['is_enabled'], $id);
                    $subModuleArr[$subModule['code']] = [
                        'name' => $subModule['name'],
                        'is_enabled' => $isEnabledFlag,
                    ];
                    if ($isEnabledFlag) {
                        $subModuleArr[$subModule['code']]['allow_view'] = $this->onIsAllowed($subModule['allow_view'], $id);
                        $subModuleArr[$subModule['code']]['allow_create'] = $this->onIsAllowed($subModule['allow_create'], $id);
                        $subModuleArr[$subModule['code']]['allow_update'] = $this->onIsAllowed($subModule['allow_update'], $id);
                        $subModuleArr[$subModule['code']]['allow_delete'] = $this->onIsAllowed($subModule['allow_delete'], $id);
                        $subModuleArr[$subModule['code']]['allow_reopen'] = $this->onIsAllowed($subModule['allow_reopen'], $id);

                    }
                }

                $isEnabledFlagModule = $this->onIsAllowed($module['is_enabled'], $id);
                $permissionList[$module['code']] = [
                    'name' => $module['name'],
                    'is_enabled' => $isEnabledFlagModule,
                    'submodules' => $subModuleArr
                ];
                if ($isEnabledFlagModule) {
                    $permissionList[$module['code']]['allow_view'] = $this->onIsAllowed($module['allow_view'], $id);
                    $permissionList[$module['code']]['allow_create'] = $this->onIsAllowed($module['allow_create'], $id);
                    $permissionList[$module['code']]['allow_update'] = $this->onIsAllowed($module['allow_update'], $id);
                    $permissionList[$module['code']]['allow_delete'] = $this->onIsAllowed($module['allow_delete'], $id);
                    $permissionList[$module['code']]['allow_reopen'] = $this->onIsAllowed($module['allow_reopen'], $id);
                }
            }

            return $this->dataResponse('success', 200, __('msg.record_found'), $permissionList);
        } catch (Exception $exception) {
            return $this->dataResponse('error', 400, __('msg.record_not_found'));
        }
    }

    public function onIsAllowed($isAllowedArr, $id)
    {
        $isAllowed = json_decode($isAllowedArr ?? '[]', true);

        return in_array($id, $isAllowed);
    }

    public function onBulkUpload(Request $request)
    {
        $fields = $request->validate([
            'bulk_data' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $bulkData = json_decode($fields['bulk_data'], true);

            foreach ($bulkData as $key => $value) {
                $empno = $value['emp_no'];
                $moduleType = $value['type'];
                $moduleCode = $value['module_code'];
                $allowView = $value['allow_view'];
                $allowCreate = $value['allow_create'];
                $allowUpdate = $value['allow_update'];
                $allowDelete = $value['allow_delete'];
                $allowReopen = $value['allow_reopen'];

                $this->checkModuleType($empno, $moduleType, $moduleCode, $allowView, $allowCreate, $allowUpdate, $allowDelete, $allowReopen);
            }
            DB::commit();

            return $this->dataResponse('success', 201, 'User Access ' . __('msg.create_success'));

        } catch (Exception $exception) {
            DB::rollBack();
            if ($exception instanceof \Illuminate\Database\QueryException && $exception->errorInfo[1] == 1364) {
                preg_match("/Field '(.*?)' doesn't have a default value/", $exception->getMessage(), $matches);
                return $this->dataResponse('error', 400, __('Field ":field" requires a default value.', ['field' => $matches[1] ?? 'unknown field']));
            }
            return $this->dataResponse('error', 400, $exception->getMessage());
        }
    }

    public function checkModuleType($empno, $moduleType, $moduleCode, $allowView, $allowCreate, $allowUpdate, $allowDelete, $allowReopen)
    {
        try {
            $moduleSubPermission = strcasecmp($moduleType, 'module') == 0 ? ModulePermissionModel::class : SubModulePermissionModel::class;
            $module = $moduleSubPermission::where('code', $moduleCode)->first();
            if ($module) {
                $fields = [
                    'allow_view' => $allowView,
                    'allow_create' => $allowCreate,
                    'allow_update' => $allowUpdate,
                    'allow_delete' => $allowDelete,
                    'allow_reopen' => $allowReopen,
                ];
                $updateNeeded = false;

                foreach ($fields as $field => $value) {
                    if ($value != "" || $value != null) {
                        $empArray = json_decode($module->$field, true) ?? [];
                        if (!in_array($empno, $empArray)) {
                            $empArray[] = $empno;
                            $module->$field = json_encode($empArray);
                            $updateNeeded = true;
                        }
                    }
                }

                $isEnabledArray = json_decode($module->is_enabled, true) ?? [];
                $hasAccess = ($allowView !== "") || ($allowCreate !== "") || ($allowUpdate !== "") || ($allowDelete !== "");

                if ($hasAccess && !in_array($empno, $isEnabledArray)) {
                    $isEnabledArray[] = $empno;
                    $module->is_enabled = json_encode($isEnabledArray);
                    $updateNeeded = true;
                }

                if ($updateNeeded) {
                    $module->save();
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }



}
