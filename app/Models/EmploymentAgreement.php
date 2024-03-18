<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentAgreement extends Model
{
    use HasFactory;

    protected $table = 'employee_agreements';

    protected $fillable = [
        'personal_information_id',
        'contract_probationary',
        'contract_regularization',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
