<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalAttachment extends Model
{
    use HasFactory;

    protected $table = 'medical_attachments';

    protected $fillable = [
        'personal_information_id',
        'attachment',
        'description',
        'status',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
