<?php

namespace App\Models\Approvals;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalConfiguration extends Model
{
    use HasFactory;
    protected $fillable = [
        'approval_workflow_id',
        'level',
        'approval_level_id',
        'approver_id',
        'created_by_id',
        'updated_by_id',
    ];

    public function approvalLevel()
    {
        return $this->belongsTo(ApprovalLevel::class, 'approval_level_id');
    }
    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function approver()
    {
        return $this->belongsTo(PersonalInformation::class, 'approver_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($approvalConfiguration) {
            $workflowId = $approvalConfiguration->approval_workflow_id;
            $level = $approvalConfiguration->level;
            ApprovalConfiguration::where('approval_workflow_id', $workflowId)
                ->where('level', '>', $level)
                ->decrement('level');
        });
    }
}
