<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovernmentInformation extends Model
{
    use HasFactory;

    protected $table = 'government_informations';

    protected $fillable = [
        'personal_information_id',
        'sss_number',
        'sss_id_pic',
        'philhealth_number',
        'philhealth_id_pic',
        'pagibig_number',
        'pagibig_id_pic',
        'tin_number',
        'tin_id_pic',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
