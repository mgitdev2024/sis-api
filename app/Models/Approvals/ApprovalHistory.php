<?php

namespace App\Models\Approvals;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_ticket_id',
        'level',
        'approval_configuration_id',
        'created_by_id',
        'updated_by_id',
    ];

    public function ticket()
    {
        return $this->belongsTo(ApprovalTicket::class, 'approval_ticket_id');
    }

    public function configuration()
    {
        return $this->belongsTo(ApprovalConfiguration::class, 'approval_configuration_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id');
    }
}
