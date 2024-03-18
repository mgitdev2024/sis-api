<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplinaryCorrectiveAction extends Model
{
    use HasFactory;
    protected $table = 'displinary_corrective_actions';

    protected $fillable = [
        'personal_information_id',
        'issued_da',
        'offense_level',
        'status',
    ];

    public function getOffenseLevelAttribute($value)
    {
        $offenseLevels = [
            0 => 'Light',
            1 => 'Moderate',
            2 => 'Serious',
        ];

        return $offenseLevels[$value];
    }

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
