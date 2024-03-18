<?php

namespace App\Models\SystemConfiguration;

use App\Models\Approvals\ApprovalTicket;
use App\Models\Approvals\ApprovalWorkflow;
use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessManagement extends Model
{
    use HasFactory;
    protected $table = 'access_managements';
    protected $fillable = [
        'access_code',
        'preset_name',
        'access_points',
        'description',
        'status',
        'approval_ticket_id',
        'created_by_id',
        'updated_by_id',
    ];

    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id');
    }

    public function approvalTicket()
    {
        return $this->belongsTo(ApprovalTicket::class, 'approval_ticket_id');
    }

    public function approvalWorkflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }
}
