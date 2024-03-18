<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentInformation extends Model
{
    use HasFactory;
    // protected $table = 'employment_informations';

    protected $fillable = [
        'personal_information_id',
        'id_picture',
        'company_id',
        'branch_id',
        'department_id',
        'section_id',
        'position_id',
        'workforce_division_id', // 1 = ML1 , 2 = ML2(managerial) , 3 = supervisory , 4 = rank & file
        'employment_classification',
        'date_hired',
        'onboarding_status',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id', 'id');
    }
}
