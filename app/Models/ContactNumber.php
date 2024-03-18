<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactNumber extends Model
{
    use HasFactory;

    protected $table = 'contact_numbers';

    protected $fillable = [
        'personal_information_id',
        'phone_number',
        'type',
        'status',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
