<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'address';

    protected $fillable = [
        'personal_information_id',
        'full_address',
        'region',
        'province',
        'district',
        'municipality',
        'city',
        'barangay',
        'zip_code',
        'status',
        'type',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
