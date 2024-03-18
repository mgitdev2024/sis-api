<?php

namespace App\Http\Controllers\SystemConfiguration;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration\ModuleFunction;
use App\Models\SystemConfiguration\SubModule;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use Symfony\Component\HttpFoundation\Response;
use DB;
use Illuminate\Database\QueryException;

class ModuleFunctionController extends Controller
{
    use CrudOperationsTrait;
    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'sub_module_id' => 'required',
            'module_permission_id' => 'required',
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->getRules());
        $module_permission_ids = json_decode($fields['module_permission_id']);
        try {
            DB::beginTransaction();
            foreach ($module_permission_ids as $module_permission_id) {

                $generateFunctionCode = $this->onGenerateFunctionCode($fields['sub_module_id'], $module_permission_id);
                $isCodeExist = ModuleFunction::where('function_code', $generateFunctionCode)->first();
                if ($isCodeExist) {
                    return $this->dataResponse('success', Response::HTTP_OK, __('msg.duplicate_entry'));
                }
                $record = new ModuleFunction();
                $fields['module_permission_id'] = $module_permission_id;
                $fields['function_code'] = $generateFunctionCode;
                $record->fill($fields);
                $record->save();
            }
            DB::commit();
            return $this->dataResponse('success', Response::HTTP_OK, __('msg.create_success'));
        } catch (QueryException $exception) {
            DB::rollBack();
            if ($exception->getCode() == 23000) {
                if (str_contains($exception->getMessage(), '1062 Duplicate entry')) {
                    return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, __('msg.duplicate_entry', ['modelName' => ModuleFunction::class]));
                }
            }
            return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, $exception->getMessage());
        }
    }
    public function onGenerateFunctionCode($subModuleId, $moduleFunction)
    {
        $submodule = SubModule::find($subModuleId);
        $internalSystemCode = $submodule->module->internalSystem->id;
        $subModuleName = substr($submodule->name, 0, 3);
        $moduleFunctionCode = $moduleFunction;
        $moduleCode = strtoupper($internalSystemCode . '-' . $subModuleName . '|' . $subModuleId . '-' . $moduleFunctionCode);
        return $moduleCode;
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ModuleFunction::class, $id, 'Module Access');
    }

    public function onGetAll()
    {
        return $this->readRecord(ModuleFunction::class, 'Module Access');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ModuleFunction::class, $request, $this->getRules(), 'Module Access', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ModuleFunction::class, $id, 'Module Access');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ModuleFunction::class, $id, 'Module Access');
    }

    public function onGetDistinct()
    {
        $dbData = ['module_name'];
        return $this->readDistinctRecord(ModuleFunction::class, 'Module Access', $dbData);
    }
}
