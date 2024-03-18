<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalBackground extends Model
{
    use HasFactory;

    protected $table = 'educational_background';

    protected $fillable = [
        'personal_information_id',
        'school_name',
        'level',
        'degrees_achieved',
        'year_graduated',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
