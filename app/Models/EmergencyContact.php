<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
    use HasFactory;

    protected $table = 'emergency_contacts';

    protected $fillable = [
        'personal_information_id',
        'phone_number',
        'name',
        'relationship',
        'status',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
