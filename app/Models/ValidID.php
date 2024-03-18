<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidID extends Model
{
    use HasFactory;

    protected $table = 'valid_ids';
    protected $fillable = [
        'personal_information_id',
        'id_type',
        'id_number',
        'attachment',
        'status',
    ];

    public function personal_information()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
