<?php

namespace App\Models\OrganizationalStructure;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PersonalInformation;

class OrganizationalStructure extends Model
{
    use HasFactory;

    protected $table = 'organizational_structure';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'section_id',
        'department_id',
        'division_id',
        'parent_id',
        'level',
        'job_id',
        'workforce_division_id',
        'status',
    ];

    public function createdBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'created_by_id', 'id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(PersonalInformation::class, 'updated_by_id', 'id');
    }
    public function parent()
    {
        return $this->belongsTo(OrganizationalStructure::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OrganizationalStructure::class, 'parent_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }
    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id', 'id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_id', 'id');
    }

    public function workforceDivision()
    {
        return $this->belongsTo(WorkforceDivision::class, 'workforce_division_id', 'id');
    }
}
