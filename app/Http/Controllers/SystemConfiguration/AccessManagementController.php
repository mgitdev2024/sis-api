<?php

namespace App\Http\Controllers\SystemConfiguration;

use App\Http\Controllers\Controller;
use App\Models\Approvals\ApprovalConfiguration;
use App\Models\Approvals\ApprovalHistory;
use App\Models\Approvals\ApprovalTicket;
use App\Models\SystemConfiguration\AccessManagement;
use App\Models\SystemConfiguration\InternalSystem;
use App\Models\SystemConfiguration\Module;
use App\Models\SystemConfiguration\ModuleFunction;
use App\Models\SystemConfiguration\SubModule;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\QueryException;
use DB;

class AccessManagementController extends Controller
{
    use CrudOperationsTrait;

    public AccessManagement $accessManagement;

    public static function getRules($currentRecordId = null)
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'description' => 'nullable',
            'access_code' => 'required|string',
            'preset_name' => 'required|string|unique:access_managements,preset_name,' . $currentRecordId,
            'access_points' => 'required|string',
            'approval_workflow_id' => 'required',
            'module_id' => 'required|exists:modules,id',
            'action' => 'required|string'
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->getRules());
        try {
            DB::beginTransaction();

            $approvalTicket = new ApprovalTicket();
            $approvalTicket->created_by_id = $fields['created_by_id'];
            $approvalTicket->approval_workflow_id = $fields['approval_workflow_id'];
            $approvalTicket->approval_ticket_code = $this->onGenerateApprovalTicketCode($fields['module_id']);
            $approvalTicket->action = $fields['action'];
            $approvalTicket->save();

            $approvalConfiguration = ApprovalConfiguration::where('approval_workflow_id', $fields['approval_workflow_id'])
                ->orderBy('level', 'ASC')
                ->get();

            $count = 1;
            foreach ($approvalConfiguration as $approvers) {
                $approvalHistory = new ApprovalHistory();
                $approvalHistory->approval_ticket_id = $approvalTicket->id;
                $approvalHistory->level = $count;
                $approvalHistory->approval_configuration_id = $approvers->id;
                $approvalHistory->created_by_id = $fields['created_by_id'];
                $count++;
                $approvalHistory->save();
            }

            $accessManagement = new AccessManagement();
            $accessManagement->created_by_id = $fields['created_by_id'];
            $accessManagement->description = $fields['description'];
            $accessManagement->preset_name = $fields['preset_name'];
            $accessManagement->access_points = $fields['access_points'];
            $accessManagement->access_code = $fields['access_code'];
            $accessManagement->approval_ticket_id = $approvalTicket->id;
            $accessManagement->save();


            DB::commit();
            return $this->dataResponse('success', Response::HTTP_OK, __('msg.create_success'));
        } catch (QueryException $exception) {
            DB::rollBack();
            if ($exception->getCode() == 23000) {
                if (str_contains($exception->getMessage(), '1062 Duplicate entry')) {
                    return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, __('msg.duplicate_entry', ['modelName' => 'Access Management']));
                }
            }
            return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, $exception->getMessage());
        }
    }
    public function onGetById($id)
    {
        return $this->readRecordById(AccessManagement::class, $id, 'Access Management');
    }

    public function onGetAll()
    {
        return $this->readRecord(AccessManagement::class, 'Access Management');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(AccessManagement::class, $request, $this->getRules($id), 'Access Management', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(AccessManagement::class, $id, 'Access Management');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(AccessManagement::class, $id, 'Access Management');
    }

    public function onGetAllConfiguration()
    {
        try {
            $configurations = [];
            $internalSystemArr = InternalSystem::get();

            foreach ($internalSystemArr as $internalSystem) {
                $systemConfiguration = [
                    'system_info' => $internalSystem,
                    'modules' => []
                ];

                $moduleResponse = Module::where('internal_system_id', $internalSystem->id)->get();
                foreach ($moduleResponse as $module) {
                    $arr_module = $module->toArray();
                    $submoduleResponse = SubModule::where('module_id', $module->id)->get();
                    foreach ($submoduleResponse as $submodule) {
                        $arr_submodule = $submodule->toArray();
                        $moduleFunctionResponse = ModuleFunction::where('sub_module_id', $submodule->id)->get();
                        foreach ($moduleFunctionResponse as $moduleFunction) {
                            $arr_submodule['module_function'][] = [
                                'id' => $moduleFunction->id,
                                'function_code' => $moduleFunction->function_code,
                                'module_permission' => $moduleFunction->modulePermission->permission_name,
                            ];
                        }
                        $arr_module['submodule'][] = $arr_submodule;
                    }
                    $systemConfiguration['modules'][] = $arr_module;
                }
                $configurations[] = $systemConfiguration;
            }
            return $this->dataResponse('success', Response::HTTP_OK, __('msg.record_found'), $configurations);
        } catch (\Exception $exception) {
            return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, $exception->getMessage());
        }
    }

    public function onGenerateApprovalTicketCode($module_id)
    {
        $module = Module::find($module_id);
        $submodule = Submodule::where('module_id', $module_id)->first();
        $internalSystem = $module->internalSystem;
        $currentLength = count(ApprovalTicket::get()) + 1;
        $code = implode('-', [
            strtoupper($internalSystem->short_name),
            strtoupper(substr($module->name, 0, 3)),
            strtoupper(substr($submodule->name ?? '0', 0, 3)),
            $currentLength
        ]);
        return $code;
    }
}
