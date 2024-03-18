<?php

namespace App\Models\OrganizationalStructure;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PersonalInformation;

class WorkforceDivision extends Model
{
    use HasFactory;
    protected $table = 'workforce_divisions';
    protected $fillable = [
        'created_by_id',
        'updated_by_id',
        'workforce_division_code',
        'workforce_division_name',
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
}
