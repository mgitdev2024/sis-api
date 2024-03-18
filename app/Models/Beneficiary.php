<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory;

    protected $table = 'beneficiaries';

    protected $fillable = [
        'personal_information_id',
        'name',
        'birth_date',
        'relationship',
        'status',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
