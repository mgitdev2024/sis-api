<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $table = 'medical_records';

    protected $fillable = [
        'personal_information_id',
        'blood_type',
        'hmo',
        'certificate_number',
        'effective_date',
        'status',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
