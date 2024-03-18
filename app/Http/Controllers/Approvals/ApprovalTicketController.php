<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Approvals\ApprovalTicket;
use App\Models\SystemConfiguration\Module;
use App\Models\SystemConfiguration\SubModule;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class ApprovalTicketController extends Controller
{
    use CrudOperationsTrait;

    public static function getRules()
    {
        return [
            'created_by_id' => 'required|exists:personal_informations,id',
            'approval_workflow_id' => 'required|string',
            'module_id' => 'required|integer',
        ];
    }
    public function onCreate(Request $request)
    {
        $fields = $request->validate($this->getRules());
        try {
            $record = new ApprovalTicket();
            $record->fill($fields);
            $record->save();
            return $this->dataResponse('success', Response::HTTP_OK, __('msg.create_success'));
        } catch (QueryException $exception) {
            if ($exception->getCode() == 23000) {
                if (str_contains($exception->getMessage(), '1062 Duplicate entry')) {
                    return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, __('msg.duplicate_entry', ['modelName' => 'Approval Ticket']));
                }
            }
            return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, $exception->getMessage());
        }
    }
    public function onGetById($id)
    {
        return $this->readRecordById(ApprovalTicket::class, $id, 'Approval Ticket');
    }

    public function onGetAll()
    {
        return $this->readRecord(ApprovalTicket::class, 'Approval Ticket');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ApprovalTicket::class, $request, $this->getRules(), 'Approval Ticket', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ApprovalTicket::class, $id, 'Approval Ticket');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ApprovalTicket::class, $id, 'Approval Ticket');
    }
}
