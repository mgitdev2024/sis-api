<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentSalaryAdjustment extends Model
{
    use HasFactory;

    protected $table = 'employment_salary_adjustments';

    protected $fillable = [
        'personal_information_id',
        'notice_personnel_action',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
