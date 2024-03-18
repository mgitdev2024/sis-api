<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Approvals\ApprovalLevel;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;

class ApprovalLevelController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'approval_code' => 'required|string|unique:approval_levels,approval_code',
            'name' => 'required|string',
        ];
    }
    public function onCreate(Request $request)
    {
        return $this->createRecord(ApprovalLevel::class, $request, $this->getRules(), 'Approval Level');
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ApprovalLevel::class, $id, 'Approval Level');
    }

    public function onGetAll()
    {
        return $this->readRecord(ApprovalLevel::class, 'Approval Level');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ApprovalLevel::class, $request, $this->getRules(), 'Approval Level', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ApprovalLevel::class, $id, 'Approval Level');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ApprovalLevel::class, $id, 'Approval Level');
    }
}
