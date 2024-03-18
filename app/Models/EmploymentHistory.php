<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentHistory extends Model
{
    use HasFactory;

    protected $table = 'employment_histories';

    protected $fillable = [
        'personal_information_id',
        'employer_name',
        'position_held',
        'service_length',
        'service_length_from',
        'service_length_to',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
