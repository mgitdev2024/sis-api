<?php

namespace App\Models\SystemConfiguration;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'approval_workflow_id',
        'internal_system_id',
        'created_by_id',
        'updated_by_id'
    ];
    public function internalSystem()
    {
        return $this->belongsTo(InternalSystem::class, 'internal_system_id');
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
