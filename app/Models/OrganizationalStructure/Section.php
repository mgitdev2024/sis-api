<?php

namespace App\Models\OrganizationalStructure;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PersonalInformation;

class Section extends Model
{
    use HasFactory;
    protected $table = 'sections';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'department_id',
        'section_code',
        'section_short_name',
        'section_long_name',
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
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}
