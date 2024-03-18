<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Approvals\ApprovalHistory;
use App\Models\Approvals\ApprovalTicket;
use Illuminate\Http\Request;
use App\Traits\CrudOperationsTrait;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use DB;

class ApprovalHistoryController extends Controller
{
    use CrudOperationsTrait;
    public function onAction(Request $request, $id)
    {
        $fields = $request->validate([
            'action' => 'required|integer|in:1,2',
            'reason' => 'required_if:action,2',
        ]);

        try {
            DB::beginTransaction();
            $approvalHistory = ApprovalHistory::find($id);
            $approvalHistory->is_approved = $fields['action'];
            $approvalHistory->save();
            DB::commit();

            $msgAction = __('msg.disapprove_success');
            if ($fields['action'] == 1) {
                $msgAction = __('msg.approve_success');
            }

            $this->onUpdateTicket($approvalHistory, $fields['reason']);
            return $this->dataResponse('success', Response::HTTP_OK, $msgAction);
        } catch (QueryException $exception) {
            DB::rollback();
            if ($exception->getCode() == 23000) {
                if (str_contains($exception->getMessage(), '1062 Duplicate entry')) {
                    return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, __('msg.duplicate_entry', ['modelName' => 'Approval History']));
                }
            }
            return $this->dataResponse('error', Response::HTTP_BAD_REQUEST, $exception->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
        }
    }
    public function onGetByCategory($categoryId)
    {
        return $this->readRecordByCategory(ApprovalHistory::class, 'approval_ticket_id', $categoryId, 'Approval History');
    }

    public function onUpdateTicket($approvalHistory, $reason)
    {
        $approvalTicketId = $approvalHistory->id;
        $approvalHistoryResponse = json_decode($this->onGetByCategory($approvalTicketId)->getContent())->success->data;
        $countLength = 0;
        $status = '';
        foreach ($approvalHistoryResponse as $data) {
            if ($data->is_approved === 1) {
                continue;
            } else if ($data->is_approved === 2) {
                $status = 'disapproved';
                break;
            }
            $countLength++;
        }
        if ($status == 'disapproved') {
            $status = 4;
        } else if ($countLength <= 0) {
            $status = 3;
        } else if ($countLength == 1) {
            $status = 2;
        } else {
            $status = 1;
        }

        try {
            DB::beginTransaction();
            $approvalTicket = ApprovalTicket::find($approvalTicketId);
            $approvalTicket->approval_status = $status;
            $approvalTicket->reason = $reason;
            $approvalTicket->save();
            DB::commit();
        } catch (\Exception $e) {
            throw new \Exception("Error Processing Request", $e->getMessage());
        }
    }

    public function onGetById($id)
    {
        return $this->readRecordById(ApprovalHistory::class, $id, 'Approval History');
    }

    public function onGetAll()
    {
        return $this->readRecord(ApprovalHistory::class, 'Approval History');
    }

    public function onUpdateById(Request $request, $id)
    {
        return $this->updateRecordById(ApprovalHistory::class, $request, $this->getRules(), 'Approval History', $id);
    }

    public function onChangeStatus($id)
    {
        return $this->changeStatusRecordById(ApprovalHistory::class, $id, 'Approval History');
    }

    public function onDeleteById($id)
    {
        return $this->deleteRecordById(ApprovalHistory::class, $id, 'Approval History');
    }
}
