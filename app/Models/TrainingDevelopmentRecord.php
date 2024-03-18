<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingDevelopmentRecord extends Model
{
    use HasFactory;

    protected $table = 'training_development_records';

    protected $fillable = [
        'personal_information_id',
        'training_attended',
        'certificate',
        'bond_contract',
        'obligatory_training',
        'nhe_orientation',
        'duration_required',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
