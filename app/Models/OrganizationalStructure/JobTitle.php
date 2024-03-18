<?php

namespace App\Models\OrganizationalStructure;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PersonalInformation;

class JobTitle extends Model
{
    use HasFactory;
    protected $table = 'job_titles';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'section_id',
        'division_id',
        'department_id',
        'job_code',
        'job_title',
        'job_description',
        'slot',
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
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    public function division()
    {
        return $this->belongsTo(Section::class, 'division_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Section::class, 'department_id', 'id');
    }
}
