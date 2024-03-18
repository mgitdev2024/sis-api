<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Approvals\ApprovalWorkflow;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ApprovalWorkflowController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'workflow_name' => 'required|string',
            'description' => 'nullable|string',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ApprovalWorkflow::class, $request, $this->getRules(), 'Approval Workflow');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ApprovalWorkflow::class, $id, 'Approval Workflow');
    }

    public function onGetAll()
    {
        return $this->readRecord(ApprovalWorkflow::class, 'Approval Workflow');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ApprovalWorkflow::class, $request, $this->getRules(), 'Approval Workflow', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ApprovalWorkflow::class, $id, 'Approval Workflow');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ApprovalWorkflow::class, $id, 'Approval Workflow');
    }
}
