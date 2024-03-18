<?php

namespace App\Models\OrganizationalStructure;

use App\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $table = 'departments';

    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'division_id',
        'department_code',
        'department_short_name',
        'department_long_name',
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
    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id', 'id');
    }
}
