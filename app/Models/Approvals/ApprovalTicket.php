<?php

namespace App\Models\Approvals;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_workflow_id',
        'approval_ticket_code',
        'approval_status',
        'action',
        'reason',
        'created_by_id',
        'updated_by_id',
    ];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id');
    }

    public function getStatusString($index)
    {
        $statuses = ['Pending', 'Under Approval Process', 'For Final Approval', 'Approved', 'Declined'];

        return $statuses[$index];
    }
}
