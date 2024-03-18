<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Approvals\ApprovalConfiguration;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ApprovalConfigurationController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'approval_workflow_id' => 'required|exists:approval_workflows,id',
            'level' => 'required|integer',
            'approval_level_id' => 'required|exists:approval_levels,id',
            'approver_id' => 'required|exists:personal_informations,id'
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ApprovalConfiguration::class, $request, $this->getRules(), 'Approval Configuration');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ApprovalConfiguration::class, $id, 'Approval Configuration');
    }

    public function onGetAll()
    {
        return $this->readRecord(ApprovalConfiguration::class, 'Approval Configuration');
    }

    public function onUpdateById(Request $request, $id)
    {
        $rules = [
            'created_by_id' => 'required|exists:personal_informations,id',
            'approval_workflow_id' => 'required|exists:approval_workflows,id',
            'level' => 'required',
            'approval_level_id' => 'required|exists:approval_levels,id',
            'approver_id' => 'required|exists:personal_informations,id'
        ];

        return $this->updateRecordById(ApprovalConfiguration::class, $request, $rules, 'Approval Configuration', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ApprovalConfiguration::class, $id, 'Approval Configuration');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ApprovalConfiguration::class, $id, 'Approval Configuration');
    }
    public function onGetByCategory($categoryId)
    {
        return $this->readRecordByCategory(ApprovalConfiguration::class, 'approval_workflow_id', $categoryId, 'Approval Configuration');
    }
}
