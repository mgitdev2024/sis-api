<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceEvaluation extends Model
{
    use HasFactory;

    protected $table = 'performance_evaluations';

    protected $fillable = [
        'personal_information_id',
        'evaluation_third',
        'evaluation_fifth',
        'appraisal',
        'pip',
        'status',
    ];

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id');
    }
}
